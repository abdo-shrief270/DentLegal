"""Service-to-service auth: Laravel sends X-Service-Token on the private network (§4.5)."""

from __future__ import annotations

import hmac
from typing import Annotated

from fastapi import Depends, Header

from app.core.config import Settings, get_settings
from app.core.errors import ServiceError


def require_service_token(
    settings: Annotated[Settings, Depends(get_settings)],
    x_service_token: Annotated[str | None, Header()] = None,
) -> None:
    expected = settings.service_token.get_secret_value()
    if not expected:
        raise ServiceError(503, "not_configured", "AI_SERVICE_TOKEN is not set")
    if x_service_token is None or not hmac.compare_digest(x_service_token.encode(), expected.encode()):
        raise ServiceError(401, "unauthenticated", "invalid service token")
