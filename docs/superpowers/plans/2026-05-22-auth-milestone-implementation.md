# Auth Milestone Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the first working auth milestone for a Laravel 13 API and Vue 3 SPA finance app.

**Architecture:** The repo is a single project with `backend/` for Laravel and `frontend/` for Vue. Laravel exposes Sanctum-protected JSON auth endpoints; Vue owns auth screens, auth state, and protected dashboard routing. Controllers stay thin, validation lives in `FormRequest` classes, and auth workflow lives in service classes.

**Tech Stack:** PHP 8.3 via `php83`, Laravel 13, Sanctum, PHPUnit, Vue 3, Vite, Pinia, Vue Router, Axios.

---

## File Structure

Create or modify these files:

- `backend/`: Laravel 13 application scaffold.
- `backend/routes/api.php`: Auth API routes.
- `backend/app/Http/Controllers/Api/Auth/RegisterController.php`: Handles registration request/response.
- `backend/app/Http/Controllers/Api/Auth/LoginController.php`: Handles login request/response.
- `backend/app/Http/Controllers/Api/Auth/LogoutController.php`: Handles logout.
- `backend/app/Http/Controllers/Api/Auth/MeController.php`: Returns authenticated user.
- `backend/app/Http/Requests/Auth/RegisterRequest.php`: Validates registration data.
- `backend/app/Http/Requests/Auth/LoginRequest.php`: Validates login data.
- `backend/app/Http/Resources/UserResource.php`: Shapes user JSON.
- `backend/app/Services/Auth/RegisterUserService.php`: Creates and logs in a new user.
- `backend/app/Services/Auth/LoginUserService.php`: Validates credentials and logs in an existing user.
- `backend/tests/Feature/Auth/RegisterTest.php`: Registration behavior tests.
- `backend/tests/Feature/Auth/LoginTest.php`: Login behavior tests.
- `backend/tests/Feature/Auth/MeTest.php`: Authenticated profile tests.
- `backend/tests/Feature/Auth/LogoutTest.php`: Logout behavior tests.
- `frontend/`: Vue 3 application scaffold.
- `frontend/src/api/axios.js`: Axios instance with cookie support.
- `frontend/src/api/authApi.js`: Auth API wrapper.
- `frontend/src/stores/authStore.js`: Pinia auth state/actions.
- `frontend/src/router/index.js`: Routes and auth guard.
- `frontend/src/layouts/AuthLayout.vue`: Layout for login/register pages.
- `frontend/src/layouts/AppLayout.vue`: Layout for dashboard.
- `frontend/src/pages/LoginPage.vue`: Login form.
- `frontend/src/pages/RegisterPage.vue`: Register form.
- `frontend/src/pages/DashboardPage.vue`: Protected dashboard shell.

## Task 1: Scaffold Applications

**Files:**
- Create: `backend/`
- Create: `frontend/`
- Modify: `docs/superpowers/specs/2026-05-22-auth-milestone-design.md`

- [ ] **Step 1: Create Laravel 13 backend with PHP 8.3**

Run:

```bash
php83 $(command -v composer) create-project laravel/laravel:^13.0 backend
```

Expected: `backend/composer.json` exists and requires `laravel/framework` version `^13.x`.

- [ ] **Step 2: Create Vue 3 frontend**

Run:

```bash
npm create vite@latest frontend -- --template vue
```

Expected: `frontend/package.json` exists and Vite Vue files are present.

- [ ] **Step 3: Install frontend dependencies**

Run:

```bash
cd frontend && npm install && npm install axios pinia vue-router
```

Expected: `frontend/package-lock.json` includes `axios`, `pinia`, and `vue-router`.

- [ ] **Step 4: Verify baseline builds**

Run:

```bash
cd backend && php83 artisan test
cd ../frontend && npm run build
```

Expected: Laravel tests pass and Vite builds successfully.

- [ ] **Step 5: Commit scaffold**

Run:

```bash
git add backend frontend docs/superpowers/specs/2026-05-22-auth-milestone-design.md
git commit -m "chore: scaffold laravel and vue apps"
```

## Task 2: Backend Auth Tests

**Files:**
- Create: `backend/tests/Feature/Auth/RegisterTest.php`
- Create: `backend/tests/Feature/Auth/LoginTest.php`
- Create: `backend/tests/Feature/Auth/MeTest.php`
- Create: `backend/tests/Feature/Auth/LogoutTest.php`

- [ ] **Step 1: Write failing registration tests**

Create `backend/tests/Feature/Auth/RegisterTest.php`:

```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('a user can register and is authenticated', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Test User')
        ->assertJsonPath('data.email', 'test@example.com');

    $this->assertAuthenticated();
    $this->assertTrue(Hash::check('password', User::first()->password));
});

test('registration rejects duplicate emails', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $response = $this->postJson('/api/register', [
        'name' => 'Second User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});
```

- [ ] **Step 2: Write failing login tests**

Create `backend/tests/Feature/Auth/LoginTest.php`:

```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('a user can log in with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', 'test@example.com');

    $this->assertAuthenticatedAs($user);
});

test('login rejects invalid credentials', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);

    $this->assertGuest();
});
```

- [ ] **Step 3: Write failing me/logout tests**

Create `backend/tests/Feature/Auth/MeTest.php`:

```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can fetch their profile', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/me');

    $response->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);
});

test('guest cannot fetch profile', function () {
    $this->getJson('/api/me')->assertUnauthorized();
});
```

Create `backend/tests/Feature/Auth/LogoutTest.php`:

```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can log out', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/logout');

    $response->assertNoContent();
    $this->assertGuest();
});

test('guest cannot log out', function () {
    $this->postJson('/api/logout')->assertUnauthorized();
});
```

- [ ] **Step 4: Run tests to verify they fail**

Run:

```bash
cd backend && php83 artisan test tests/Feature/Auth
```

Expected: Tests fail because `/api/register`, `/api/login`, `/api/me`, and `/api/logout` are not implemented yet.

## Task 3: Backend Auth Implementation

**Files:**
- Create: `backend/app/Http/Controllers/Api/Auth/RegisterController.php`
- Create: `backend/app/Http/Controllers/Api/Auth/LoginController.php`
- Create: `backend/app/Http/Controllers/Api/Auth/LogoutController.php`
- Create: `backend/app/Http/Controllers/Api/Auth/MeController.php`
- Create: `backend/app/Http/Requests/Auth/RegisterRequest.php`
- Create: `backend/app/Http/Requests/Auth/LoginRequest.php`
- Create: `backend/app/Http/Resources/UserResource.php`
- Create: `backend/app/Services/Auth/RegisterUserService.php`
- Create: `backend/app/Services/Auth/LoginUserService.php`
- Modify: `backend/routes/api.php`

- [ ] **Step 1: Add request validation classes**

Create `backend/app/Http/Requests/Auth/RegisterRequest.php`:

```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /** Determine whether the current request can register a user. */
    public function authorize(): bool
    {
        return true;
    }

    /** Get the validation rules for user registration. */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }
}
```

Create `backend/app/Http/Requests/Auth/LoginRequest.php`:

```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /** Determine whether the current request can attempt login. */
    public function authorize(): bool
    {
        return true;
    }

    /** Get the validation rules for user login. */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
```

- [ ] **Step 2: Add auth services**

Create `backend/app/Services/Auth/RegisterUserService.php`:

```php
<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterUserService
{
    /** Create a user from validated registration data and log them in. */
    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user);

        return $user;
    }
}
```

Create `backend/app/Services/Auth/LoginUserService.php`:

```php
<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginUserService
{
    /** Attempt to authenticate a user with validated credentials. */
    public function login(array $credentials): User
    {
        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        request()->session()->regenerate();

        return Auth::user();
    }
}
```

- [ ] **Step 3: Add user resource**

Create `backend/app/Http/Resources/UserResource.php`:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /** Convert the authenticated user into an API response array. */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
```

- [ ] **Step 4: Add auth controllers**

Create `backend/app/Http/Controllers/Api/Auth/RegisterController.php`:

```php
<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\RegisterUserService;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    /** Register a new user and return their profile. */
    public function __invoke(RegisterRequest $request, RegisterUserService $service): JsonResponse
    {
        $user = $service->register($request->validated());

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }
}
```

Create `backend/app/Http/Controllers/Api/Auth/LoginController.php`:

```php
<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\LoginUserService;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    /** Log in an existing user and return their profile. */
    public function __invoke(LoginRequest $request, LoginUserService $service): JsonResponse
    {
        $user = $service->login($request->validated());

        return (new UserResource($user))->response();
    }
}
```

Create `backend/app/Http/Controllers/Api/Auth/LogoutController.php`:

```php
<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    /** Log out the current user and invalidate their session. */
    public function __invoke(): Response
    {
        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return response()->noContent();
    }
}
```

Create `backend/app/Http/Controllers/Api/Auth/MeController.php`:

```php
<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;

class MeController extends Controller
{
    /** Return the currently authenticated user's profile. */
    public function __invoke(): UserResource
    {
        return new UserResource(request()->user());
    }
}
```

- [ ] **Step 5: Register API routes**

Replace `backend/routes/api.php` with:

```php
<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

Route::post('/register', RegisterController::class)->middleware('guest');
Route::post('/login', LoginController::class)->middleware('guest');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', MeController::class);
    Route::post('/logout', LogoutController::class);
});
```

- [ ] **Step 6: Run backend auth tests**

Run:

```bash
cd backend && php83 artisan test tests/Feature/Auth
```

Expected: All auth feature tests pass.

- [ ] **Step 7: Run full backend test suite**

Run:

```bash
cd backend && php83 artisan test
```

Expected: All backend tests pass.

- [ ] **Step 8: Commit backend auth**

Run:

```bash
git add backend
git commit -m "feat: add sanctum auth api"
```

## Task 4: Frontend Auth Implementation

**Files:**
- Modify: `frontend/src/main.js`
- Modify: `frontend/src/App.vue`
- Create: `frontend/src/api/axios.js`
- Create: `frontend/src/api/authApi.js`
- Create: `frontend/src/stores/authStore.js`
- Create: `frontend/src/router/index.js`
- Create: `frontend/src/layouts/AuthLayout.vue`
- Create: `frontend/src/layouts/AppLayout.vue`
- Create: `frontend/src/pages/LoginPage.vue`
- Create: `frontend/src/pages/RegisterPage.vue`
- Create: `frontend/src/pages/DashboardPage.vue`

- [ ] **Step 1: Add Axios API client**

Create `frontend/src/api/axios.js`:

```js
import axios from 'axios'

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/',
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
  },
})

export async function ensureCsrfCookie() {
  await api.get('/sanctum/csrf-cookie')
}
```

- [ ] **Step 2: Add auth API wrapper**

Create `frontend/src/api/authApi.js`:

```js
import { api, ensureCsrfCookie } from './axios'

export async function register(payload) {
  await ensureCsrfCookie()
  return api.post('/api/register', payload)
}

export async function login(payload) {
  await ensureCsrfCookie()
  return api.post('/api/login', payload)
}

export async function logout() {
  return api.post('/api/logout')
}

export async function fetchMe() {
  return api.get('/api/me')
}
```

- [ ] **Step 3: Add Pinia auth store**

Create `frontend/src/stores/authStore.js`:

```js
import { defineStore } from 'pinia'
import * as authApi from '../api/authApi'

function validationErrors(error) {
  return error.response?.data?.errors || {}
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    loaded: false,
    loading: false,
    errors: {},
  }),
  getters: {
    isAuthenticated: (state) => Boolean(state.user),
  },
  actions: {
    async fetchUser() {
      try {
        const response = await authApi.fetchMe()
        this.user = response.data.data
      } catch (error) {
        this.user = null
      } finally {
        this.loaded = true
      }
    },
    async register(payload) {
      this.loading = true
      this.errors = {}
      try {
        const response = await authApi.register(payload)
        this.user = response.data.data
      } catch (error) {
        this.errors = validationErrors(error)
        throw error
      } finally {
        this.loading = false
        this.loaded = true
      }
    },
    async login(payload) {
      this.loading = true
      this.errors = {}
      try {
        const response = await authApi.login(payload)
        this.user = response.data.data
      } catch (error) {
        this.errors = validationErrors(error)
        throw error
      } finally {
        this.loading = false
        this.loaded = true
      }
    },
    async logout() {
      await authApi.logout()
      this.user = null
      this.loaded = true
      this.errors = {}
    },
  },
})
```

- [ ] **Step 4: Add router and route guard**

Create `frontend/src/router/index.js`:

```js
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/authStore'
import LoginPage from '../pages/LoginPage.vue'
import RegisterPage from '../pages/RegisterPage.vue'
import DashboardPage from '../pages/DashboardPage.vue'

const routes = [
  { path: '/', redirect: '/dashboard' },
  { path: '/login', name: 'login', component: LoginPage, meta: { guest: true } },
  { path: '/register', name: 'register', component: RegisterPage, meta: { guest: true } },
  { path: '/dashboard', name: 'dashboard', component: DashboardPage, meta: { requiresAuth: true } },
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (!auth.loaded) {
    await auth.fetchUser()
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login' }
  }

  if (to.meta.guest && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }
})
```

- [ ] **Step 5: Wire Vue app**

Replace `frontend/src/main.js` with:

```js
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './style.css'
import App from './App.vue'
import { router } from './router'

createApp(App)
  .use(createPinia())
  .use(router)
  .mount('#app')
```

Replace `frontend/src/App.vue` with:

```vue
<template>
  <RouterView />
</template>
```

- [ ] **Step 6: Add layouts and pages**

Create `frontend/src/layouts/AuthLayout.vue`:

```vue
<template>
  <main class="auth-layout">
    <section class="auth-panel">
      <slot />
    </section>
  </main>
</template>
```

Create `frontend/src/layouts/AppLayout.vue`:

```vue
<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/authStore'

const auth = useAuthStore()
const router = useRouter()

async function signOut() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="app-layout">
    <header class="app-header">
      <strong>Finance App</strong>
      <button type="button" @click="signOut">Logout</button>
    </header>
    <main class="app-main">
      <slot />
    </main>
  </div>
</template>
```

Create `frontend/src/pages/LoginPage.vue`:

```vue
<script setup>
import { reactive } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import AuthLayout from '../layouts/AuthLayout.vue'
import { useAuthStore } from '../stores/authStore'

const auth = useAuthStore()
const router = useRouter()
const form = reactive({
  email: '',
  password: '',
})

async function submit() {
  await auth.login(form)
  router.push({ name: 'dashboard' })
}
</script>

<template>
  <AuthLayout>
    <h1>Login</h1>
    <form class="form-stack" @submit.prevent="submit">
      <label>
        Email
        <input v-model="form.email" type="email" autocomplete="email" required>
      </label>
      <p v-if="auth.errors.email" class="field-error">{{ auth.errors.email[0] }}</p>

      <label>
        Password
        <input v-model="form.password" type="password" autocomplete="current-password" required>
      </label>
      <p v-if="auth.errors.password" class="field-error">{{ auth.errors.password[0] }}</p>

      <button type="submit" :disabled="auth.loading">
        {{ auth.loading ? 'Logging in...' : 'Login' }}
      </button>
    </form>
    <p class="auth-switch">
      New here?
      <RouterLink to="/register">Create an account</RouterLink>
    </p>
  </AuthLayout>
</template>
```

Create `frontend/src/pages/RegisterPage.vue`:

```vue
<script setup>
import { reactive } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import AuthLayout from '../layouts/AuthLayout.vue'
import { useAuthStore } from '../stores/authStore'

const auth = useAuthStore()
const router = useRouter()
const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

async function submit() {
  await auth.register(form)
  router.push({ name: 'dashboard' })
}
</script>

<template>
  <AuthLayout>
    <h1>Create account</h1>
    <form class="form-stack" @submit.prevent="submit">
      <label>
        Name
        <input v-model="form.name" type="text" autocomplete="name" required>
      </label>
      <p v-if="auth.errors.name" class="field-error">{{ auth.errors.name[0] }}</p>

      <label>
        Email
        <input v-model="form.email" type="email" autocomplete="email" required>
      </label>
      <p v-if="auth.errors.email" class="field-error">{{ auth.errors.email[0] }}</p>

      <label>
        Password
        <input v-model="form.password" type="password" autocomplete="new-password" required>
      </label>
      <p v-if="auth.errors.password" class="field-error">{{ auth.errors.password[0] }}</p>

      <label>
        Confirm password
        <input v-model="form.password_confirmation" type="password" autocomplete="new-password" required>
      </label>

      <button type="submit" :disabled="auth.loading">
        {{ auth.loading ? 'Creating...' : 'Create account' }}
      </button>
    </form>
    <p class="auth-switch">
      Already registered?
      <RouterLink to="/login">Login</RouterLink>
    </p>
  </AuthLayout>
</template>
```

Create `frontend/src/pages/DashboardPage.vue`:

```vue
<script setup>
import AppLayout from '../layouts/AppLayout.vue'
import { useAuthStore } from '../stores/authStore'

const auth = useAuthStore()
</script>

<template>
  <AppLayout>
    <section class="dashboard-shell">
      <p class="eyebrow">Dashboard</p>
      <h1>Welcome, {{ auth.user?.name }}</h1>
      <p>Your finance dashboard is ready for the next milestone.</p>
    </section>
  </AppLayout>
</template>
```

Replace `frontend/src/style.css` with:

```css
* {
  box-sizing: border-box;
}

body {
  margin: 0;
  min-width: 320px;
  min-height: 100vh;
  color: #172026;
  background: #f4f7f5;
  font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

button,
input {
  font: inherit;
}

button {
  min-height: 44px;
  border: 0;
  border-radius: 6px;
  color: #ffffff;
  background: #176b5b;
  cursor: pointer;
}

button:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

a {
  color: #176b5b;
}

.auth-layout {
  display: grid;
  min-height: 100vh;
  place-items: center;
  padding: 24px;
}

.auth-panel {
  width: min(100%, 420px);
  padding: 28px;
  border: 1px solid #d8e2dc;
  border-radius: 8px;
  background: #ffffff;
}

.form-stack {
  display: grid;
  gap: 14px;
}

.form-stack label {
  display: grid;
  gap: 6px;
  font-weight: 600;
}

.form-stack input {
  width: 100%;
  min-height: 44px;
  border: 1px solid #c8d6d1;
  border-radius: 6px;
  padding: 10px 12px;
}

.field-error {
  margin: -8px 0 0;
  color: #b42318;
  font-size: 0.9rem;
}

.auth-switch {
  margin: 18px 0 0;
}

.app-layout {
  min-height: 100vh;
}

.app-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 24px;
  border-bottom: 1px solid #d8e2dc;
  background: #ffffff;
}

.app-header button {
  padding: 0 16px;
}

.app-main {
  width: min(100%, 960px);
  margin: 0 auto;
  padding: 32px 24px;
}

.dashboard-shell {
  padding: 24px 0;
}

.eyebrow {
  margin: 0;
  color: #176b5b;
  font-weight: 700;
  text-transform: uppercase;
}
```

- [ ] **Step 7: Verify frontend build**

Run:

```bash
cd frontend && npm run build
```

Expected: Vite build passes.

- [ ] **Step 8: Commit frontend auth**

Run:

```bash
git add frontend
git commit -m "feat: add vue auth shell"
```

## Task 5: Final Verification

**Files:**
- Modify if needed: `backend/.env.example`
- Modify if needed: `frontend/.env.example`

- [ ] **Step 1: Run backend tests**

Run:

```bash
cd backend && php83 artisan test
```

Expected: All backend tests pass.

- [ ] **Step 2: Run frontend build**

Run:

```bash
cd frontend && npm run build
```

Expected: Vite build passes.

- [ ] **Step 3: Report local run commands**

Use:

```bash
cd backend && php83 artisan serve --host=127.0.0.1 --port=8000
cd frontend && npm run dev -- --host 127.0.0.1
```

Expected: Laravel API available at `http://127.0.0.1:8000` and Vue app available at the Vite URL.

## Self-Review

- Spec coverage: Plan covers repo structure, Laravel 13/PHP 8.3, Vue 3, Sanctum auth, service layer, auth routes, frontend auth state, protected dashboard, testing, and excluded later-phase items.
- Placeholder scan: No `TBD`, `TODO`, or unresolved implementation placeholders remain.
- Type consistency: Backend class names, route paths, Vue module names, and auth store method names match across tasks.
