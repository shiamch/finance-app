# Auth Milestone Design

Date: 2026-05-22
Project root: `/Users/shiamchowdhury/Documents/projects/finance-app`

## Goal

Build the first usable authentication milestone for a multi-user personal finance application. Users can register with email/password, log in, access their own dashboard shell, fetch their authenticated profile, and log out.

This milestone intentionally avoids finance features so the authentication foundation can be finished, deployed, and verified before building accounts, transactions, budgets, and reports.

## Decisions

- Use a single Git repository with two app folders: `backend/` for Laravel and `frontend/` for Vue 3.
- Use Laravel 13 as an API backend, not Blade templating.
- Use PHP 8.3 for local development and VPS deployment.
- Use Vue 3 with Vite for the frontend SPA.
- Use Laravel Sanctum SPA cookie authentication.
- Deploy under one domain, with Vue serving the app and Laravel handling `/api` routes.
- Keep controllers thin and move meaningful business workflow into service classes.
- Use normal email/password registration in Phase 1.
- Defer email verification, Google OAuth, password reset, and finance modules to later phases.

## Repository Structure

```text
finance-app/
  backend/
  frontend/
  docs/
    superpowers/
      specs/
  deploy/
  README.md
```

## Backend Architecture

The backend will be a Laravel API application.

```text
backend/app/
  Http/
    Controllers/Api/Auth/
      RegisterController.php
      LoginController.php
      LogoutController.php
      MeController.php
    Requests/Auth/
      RegisterRequest.php
      LoginRequest.php
    Resources/
      UserResource.php
  Services/
    Auth/
      RegisterUserService.php
      LoginUserService.php
  Models/
    User.php
```

Responsibilities:

- `FormRequest` classes validate incoming payloads.
- Controllers coordinate the request, service call, and API response.
- Services contain auth workflow decisions such as creating a user, checking credentials, and logging the user in.
- `UserResource` controls the authenticated user response shape.
- Laravel Sanctum manages browser session authentication for the Vue SPA.

Phase 1 API routes:

```text
POST /api/register
POST /api/login
POST /api/logout
GET  /api/me
```

## Frontend Architecture

The frontend will be a Vue 3 SPA.

```text
frontend/src/
  api/
    axios.js
    authApi.js
  stores/
    authStore.js
  router/
    index.js
  pages/
    LoginPage.vue
    RegisterPage.vue
    DashboardPage.vue
  layouts/
    AuthLayout.vue
    AppLayout.vue
```

Responsibilities:

- `axios.js` configures `withCredentials`, base URL behavior, and common error handling hooks.
- `authApi.js` wraps auth endpoint calls.
- `authStore.js` owns current user state, loading state, login/register/logout actions, and `fetchUser`.
- Vue Router protects dashboard routes and redirects unauthenticated users to login.
- The dashboard in Phase 1 is a shell only. Finance widgets are out of scope.

## Auth Flows

Registration:

```text
Vue -> GET /sanctum/csrf-cookie
Vue -> POST /api/register
Laravel -> validate request
Laravel -> create user
Laravel -> log user in
Vue -> GET /api/me
Vue -> redirect to dashboard
```

Login:

```text
Vue -> GET /sanctum/csrf-cookie
Vue -> POST /api/login
Laravel -> validate credentials
Laravel -> log user in
Vue -> GET /api/me
Vue -> redirect to dashboard
```

Logout:

```text
Vue -> POST /api/logout
Laravel -> invalidate session
Vue -> clear auth store
Vue -> redirect to login
```

App startup:

```text
Vue app starts
authStore.fetchUser()
GET /api/me
If user exists, allow protected routes
If user is unauthenticated, redirect to login for protected routes
```

## Deployment Shape

Recommended production layout:

```text
Browser
  -> finance.example.com
    -> Vue SPA static files
    -> /api handled by Laravel

VPS
  -> Nginx
  -> PHP-FPM
  -> MySQL or PostgreSQL
```

This same-domain deployment keeps Sanctum SPA cookie auth simpler and avoids unnecessary CORS complexity for the first production version.

## Future OAuth Readiness

Google OAuth is not part of Phase 1, but the data model should not block it. When OAuth is added, use a separate table rather than forcing provider data into `users`.

```text
social_accounts
  id
  user_id
  provider
  provider_id
  provider_email
  timestamps
```

## Exclusions

Phase 1 does not include:

- Email verification
- Google OAuth
- Password reset
- Account, transaction, budget, report, or export features
- Queue workers
- Redis
- Production deployment automation

## Testing Expectations

Backend feature tests should cover:

- User can register with valid data.
- Duplicate email registration fails.
- User can log in with valid credentials.
- Login fails with invalid credentials.
- Authenticated user can fetch `/api/me`.
- Unauthenticated user cannot fetch `/api/me`.
- Authenticated user can log out.

Frontend verification should cover:

- Register form submits successfully and redirects to dashboard.
- Login form submits successfully and redirects to dashboard.
- Protected dashboard route redirects unauthenticated visitors to login.
- Logout clears user state and redirects to login.
- API validation errors render in the relevant form.

## Open Implementation Order

Implementation should start with backend auth endpoints and tests, then add Vue auth screens and route guards, then verify the full browser flow locally before any VPS deployment work.
