"""One error envelope, mirroring the Laravel API: {"error": {"code", "message"}}. Messages are for logs, not users."""

from __future__ import annotations

from fastapi import FastAPI, Request
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse


class ServiceError(Exception):
    def __init__(self, status: int, code: str, message: str, retryable: bool = False) -> None:
        super().__init__(message)
        self.status = status
        self.code = code
        self.message = message
        self.retryable = retryable


def register_error_handlers(app: FastAPI) -> None:
    @app.exception_handler(ServiceError)
    async def _service_error(_: Request, exc: ServiceError) -> JSONResponse:
        return JSONResponse(
            status_code=exc.status, content={"error": {"code": exc.code, "message": exc.message, "retryable": exc.retryable}}
        )

    @app.exception_handler(RequestValidationError)
    async def _validation_error(_: Request, exc: RequestValidationError) -> JSONResponse:
        # Field locations only: the offending input values may be case text.
        fields = sorted({".".join(str(p) for p in err["loc"]) for err in exc.errors()})
        return JSONResponse(status_code=422, content={"error": {"code": "invalid_request", "message": "invalid request", "fields": fields}})
