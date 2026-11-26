"""ai-svc entry point: `uvicorn app.main:app` (2 workers in production, models preloaded at start-up)."""

from __future__ import annotations

from collections.abc import AsyncIterator
from contextlib import asynccontextmanager
from typing import Any

from fastapi import FastAPI

from app.core.config import Settings, get_settings
from app.core.container import Container, build_container
from app.core.error_tracking import init_error_tracking
from app.core.errors import register_error_handlers
from app.core.logging import configure_logging
from app.routes.v1 import router


def create_app(settings: Settings | None = None, container: Container | None = None, anthropic_client: Any | None = None) -> FastAPI:
    settings = settings or get_settings()
    configure_logging()
    init_error_tracking(settings)

    @asynccontextmanager
    async def lifespan(app: FastAPI) -> AsyncIterator[None]:
        app.state.container = container or build_container(settings, anthropic_client)
        yield
        if container is None:
            app.state.container.close()

    # No public docs: this API is internal to the private network.
    app = FastAPI(title="DentLegal ai-svc", version="0.1.0", lifespan=lifespan, docs_url=None, redoc_url=None, openapi_url=None)
    register_error_handlers(app)
    app.include_router(router)
    return app


app = create_app()
