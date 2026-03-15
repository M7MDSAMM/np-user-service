# User Service (Port 8001)

Stateless Laravel 12 JSON API responsible for **admin authentication**, **admin management**, and **recipient user management** (users, preferences, devices). It issues RS256 JWTs consumed by all other services and is the sole authority on identity in the platform.

## Responsibilities

- Admin authentication: login with email/password, receive JWT with role claims.
- Admin profile retrieval and management.
- Admin CRUD with role-based access (super_admin gated).
- Recipient user CRUD with pagination and filters.
- Notification preferences per user: channel toggles (`email_enabled`, `sms_enabled`, `push_enabled`), quiet hours.
- Device token management: register and delete push tokens (FCM/APNs).
- JWT key generation (`php artisan jwt:generate-keys`).

## Database

**Database:** `np_user_service`

| Table | Purpose |
|-------|---------|
| `admins` | Admin accounts: name, email, password, role (super_admin / admin), is_active |
| `users` | Recipient users: name, email, phone, is_active |
| `user_preferences` | Per-user notification channel toggles and quiet hours |
| `user_devices` | Push notification device tokens with platform info |

## API Endpoints

All routes are prefixed with `/api/v1`. Protected routes require `Authorization: Bearer <JWT>`.

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/health` | Public | Service health check |
| `POST` | `/admin/auth/login` | Public | Admin login, returns JWT |
| `GET` | `/admin/me` | Admin | Get authenticated admin profile |
| `GET` | `/admins` | Super Admin | List admins |
| `POST` | `/admins` | Super Admin | Create admin |
| `GET` | `/admins/{uuid}` | Super Admin | Get admin by UUID |
| `PUT` | `/admins/{uuid}` | Super Admin | Update admin |
| `PATCH` | `/admins/{uuid}/toggle-active` | Super Admin | Toggle admin active status |
| `GET` | `/users` | Admin | List users (filterable) |
| `POST` | `/users` | Admin | Create user |
| `GET` | `/users/{uuid}` | Admin | Get user by UUID |
| `PUT` | `/users/{uuid}` | Admin | Update user |
| `DELETE` | `/users/{uuid}` | Admin | Soft-delete user |
| `GET` | `/users/{uuid}/preferences` | Admin | Get user preferences |
| `PUT` | `/users/{uuid}/preferences` | Admin | Update user preferences |
| `GET` | `/users/{uuid}/devices` | Admin | List user devices |
| `POST` | `/users/{uuid}/devices` | Admin | Register device token |
| `DELETE` | `/users/{uuid}/devices/{deviceUuid}` | Admin | Delete device |

## Architecture

- **Tech**: Laravel 12, PHP 8.2, MySQL.
- **Security**: RS256-signed JWTs (`Rs256JwtTokenService`) with issuer/audience validation. Keys generated via `php artisan jwt:generate-keys`.
- **Middleware**:
  - `CorrelationIdMiddleware` — propagates `X-Correlation-Id` on every request/response.
  - `RequestTimingMiddleware` — logs method, route, status, latency, actor in structured JSON.
  - `JwtAdminAuthMiddleware` — validates Bearer token; returns standardized error envelope on 401/403.
  - `RequireSuperAdminMiddleware` — gates admin management routes.
- **Logging**: Structured JSON to `storage/logs/app.log` (fields: `service`, `correlation_id`, `status_code`, `latency_ms`, `actor`).
- **Responses**: Standardized API envelope (`success`, `message`, `data`, `meta`, `correlation_id`).

## Local Setup

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan jwt:generate-keys
php artisan migrate
php artisan serve --port=8001
```

Requires MySQL with database `np_user_service` created.

## Testing

```bash
php artisan test
```

Tests run against MySQL database `np_user_service_test` (configured in `phpunit.xml`). Uses `RefreshDatabase` for isolation.

**Test coverage:** 56 tests, 465 assertions — covers admin auth (login, token validation, malformed tokens), admin CRUD, user CRUD, preferences, devices, validation, and authorization.

## Notes

- This is a **leaf service** — it does not make outbound calls to other services.
- The JWT public key must be shared with (or accessible to) all other services for token verification.
- Admin numeric IDs are never exposed in API responses; only UUIDs are used.
