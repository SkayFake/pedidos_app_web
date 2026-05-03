# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {TOKEN_SANCTUM}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Obtén tu token usando `POST /api/v1/auth/login` o `POST /api/v1/auth/register`. Envía el token en el header: `Authorization: Bearer {token}`.
