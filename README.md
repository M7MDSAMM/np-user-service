# User Service (Port 8001)

Stateless Laravel 12 JSON API responsible for **admin authentication**, **admin profile**, and **recipient user management** (users, preferences, devices). It issues JWTs used by the Admin Dashboard and enforces authorization for admin-only endpoints.

## Responsibilities
- Admin auth: `POST /api/v1/admin/auth/login` → JWT, `expires_in`, `token_type`.
- Admin profile: `GET /api/v1/admin/me`.
- Recipient users: CRUD, pagination, filters.
- Preferences: channel toggles, quiet hours.
- Devices: register/delete push tokens.
- AuthZ: `JwtAdminAuthMiddleware` validates admin JWT on protected routes; `RequireSuperAdminMiddleware` gates super-admin-only actions.

## Architecture
- **Tech**: Laravel 12, PHP 8.2, MySQL.
- **Security**: RS256-signed JWTs (`Rs256JwtTokenService`) with issuer/audience checks.
- **Middleware**:
  - `CorrelationIdMiddleware` – guarantees `X-Correlation-Id` header and echoes it.
  - `RequestTimingMiddleware` – logs structured JSON with method/route/status/latency/actor.
  - `JwtAdminAuthMiddleware` – validates Authorization bearer token; returns standardized envelope on 401/403.
- **Logging**: Structured JSON to `storage/logs/app.log` (fields include `service`, `correlation_id`, `status_code`, `latency_ms`, `actor`).
- **Health**: `GET /api/v1/health` returns `{service,status,timestamp,version}` and `X-Correlation-Id`.

## Data ownership
- Database: `np_user_service` (admins, users, preferences, devices).
- Other services must access data via these APIs; there is no cross-DB access.

## Running locally
```bash
cp .env.example .env
php artisan key:generate
composer install
php artisan jwt:generate-keys   # creates RSA keypair for JWTs
php artisan migrate
php artisan serve --port=8001
```
Requires MySQL reachable per `.env` (`DB_DATABASE=np_user_service`).

## Tests
```bash
php artisan test
```
Note: Feature tests expect MySQL database `np_user_service_test` available at `DB_DATABASE` in `phpunit.xml`.

## Core endpoints
- `POST /api/v1/admin/auth/login`
- `GET /api/v1/admin/me`
- `GET /api/v1/admins` (+ CRUD, toggle-active) **(super_admin)**
- `GET /api/v1/users` (+ CRUD)
- `GET /api/v1/users/{uuid}/preferences` (+ update)
- `GET /api/v1/users/{uuid}/devices` (+ register/delete)
- `GET /api/v1/health`

All admin routes require `Authorization: Bearer <JWT>` and `X-Correlation-Id`.

## Observability
- Correlation ID is propagated and echoed on every response.
- Request timing and JWT actor (admin UUID) included in logs.
- Standardized API responses (`ApiResponse`) with `success`, `error_code`, `correlation_id`.
