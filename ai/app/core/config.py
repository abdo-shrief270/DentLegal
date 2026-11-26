"""Settings from environment variables (prefix AI_) plus config/retrieval.yaml."""

from __future__ import annotations

from functools import lru_cache
from pathlib import Path
from typing import Literal

import yaml
from pydantic import BaseModel, Field, SecretStr
from pydantic_settings import BaseSettings, SettingsConfigDict

CONFIG_DIR = Path(__file__).resolve().parents[2] / "config"


class RetrievalParams(BaseModel):
    """Retrieval knobs (PROJECT1_PLAN.md §5.5). Calibrated in S2.2 and S8.3; kept in config/retrieval.yaml."""

    k_dense: int = 30
    k_sparse: int = 30
    k_rerank: int = 30
    rrf_k: int = 60
    explicit_ref_boost: float = 0.05
    trigram_min_hits: int = 5
    trigram_threshold: float = 0.2
    tau_keep: float = 0.20
    tau_answer: float = 0.35
    top_consult: int = 8
    top_case: int = 15
    hnsw_ef_search: int = 100


class Prices(BaseModel):
    """USD per million tokens at list price (claude-opus-5-5), for ai_runs.cost_usd (§5.13)."""

    input: float = 4.0
    output: float = 20.0
    cache_write_5m: float = 5.0
    cache_read: float = 0.20
    us_geo_multiplier: float = 1.1


class Playbooks(BaseModel):
    """Fixed retrieval queries per case type and claim type (config/playbooks.yaml)."""

    common: list[str] = Field(default_factory=list)
    case_type: dict[str, list[str]] = Field(default_factory=dict)
    claim_type: dict[str, list[str]] = Field(default_factory=dict)

    def queries(self, case_type: str, claim_type: str) -> list[str]:
        return [*self.common, *self.case_type.get(case_type, []), *self.claim_type.get(claim_type, [])]


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_prefix="AI_", env_file=".env", extra="ignore")

    env: Literal["local", "testing", "staging", "production"] = "local"
    service_token: SecretStr = SecretStr("")
    # Key for the hashes in the masking placeholder state. Same value on every ai-svc worker.
    masking_secret: SecretStr = SecretStr("")
    # Error tracking (§8.6, D14): a Sentry or GlitchTip DSN; empty turns it off. `release` is set by the deploy.
    error_tracking_dsn: SecretStr = SecretStr("")
    release: str = ""
    database_url: str = "postgresql://dentlegal:dentlegal@127.0.0.1:5432/dentlegal"

    # Models (§5.4). "hashing"/"lexical" are deterministic stand-ins for tests and local work without the weights.
    embedder: Literal["bge-m3", "hashing"] = "bge-m3"
    reranker: Literal["bge-reranker-v2-m3", "lexical"] = "bge-reranker-v2-m3"
    # Arabic NER for masking (§5.10, layer 4). "none" is refused outside local/testing: masking fails closed.
    ner: Literal["camel", "none"] = "camel"
    ocr_languages: str = "ara+eng"

    # Claude (§5.6). The API key is read by the SDK from ANTHROPIC_API_KEY.
    anthropic_model: str = "claude-opus-5-5"
    inference_geo: Literal["us", "global"] = "us"
    use_fallbacks: bool = True
    anthropic_max_retries: int = 3
    consult_effort: Literal["low", "medium", "high", "xhigh", "max"] = "low"
    consult_max_tokens: int = 8000
    analysis_effort: Literal["low", "medium", "high", "xhigh", "max"] = "high"
    draft_effort: Literal["low", "medium", "high", "xhigh", "max"] = "high"
    case_max_tokens: int = 32000
    # Bundles above this are refused (bundle_too_large), never truncated (§5.6 size guard).
    case_bundle_max_tokens: int = 150_000

    prices: Prices = Field(default_factory=Prices)
    retrieval: RetrievalParams = Field(default_factory=lambda: load_retrieval_params())
    playbooks: Playbooks = Field(default_factory=lambda: load_playbooks())

    def is_test_like(self) -> bool:
        return self.env in ("local", "testing")

    def check_production_safety(self) -> None:
        """Stand-in models and disabled NER are for local work and tests only; refuse to start with them elsewhere."""
        if self.is_test_like():
            return
        problems = []
        if self.embedder != "bge-m3" or self.reranker != "bge-reranker-v2-m3":
            problems.append("stand-in embedding/reranking models")
        if self.ner != "camel":
            problems.append("NER disabled (masking would not be complete)")
        for name, secret in (("AI_SERVICE_TOKEN", self.service_token), ("AI_MASKING_SECRET", self.masking_secret)):
            value = secret.get_secret_value()
            if len(value) < 32 or "change-me" in value:
                problems.append(f"{name} missing, a placeholder, or shorter than 32 characters")
        if problems:
            raise RuntimeError(f"unsafe configuration for {self.env}: " + "; ".join(problems))


def load_retrieval_params(path: Path | None = None) -> RetrievalParams:
    path = path or CONFIG_DIR / "retrieval.yaml"
    if not path.exists():
        return RetrievalParams()
    return RetrievalParams.model_validate(yaml.safe_load(path.read_text(encoding="utf-8")) or {})


def load_playbooks(path: Path | None = None) -> Playbooks:
    path = path or CONFIG_DIR / "playbooks.yaml"
    if not path.exists():
        return Playbooks()
    return Playbooks.model_validate(yaml.safe_load(path.read_text(encoding="utf-8")) or {})


@lru_cache
def get_settings() -> Settings:
    return Settings()
