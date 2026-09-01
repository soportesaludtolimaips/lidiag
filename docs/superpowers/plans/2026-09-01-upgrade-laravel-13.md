# Upgrade Laravel 12 → 13 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Upgrade LIDiag from Laravel 12.68.0 to Laravel 13.x with PHP 8.3+

**Architecture:** Straightforward framework upgrade — update composer.json constraints, resolve dependencies, handle breaking changes (CSRF middleware rename, cache prefixes, session serialization), clear caches.

**Tech Stack:** PHP 8.3.30, Laravel 13, Composer

## Global Constraints
- PHP 8.3+ required (already have 8.3.30)
- Zero breaking changes from Laravel 12 (per official upgrade guide)
- `.env`/`.env.example` NOT committed (contain secrets)
- User pushes manually via `git push origin main --force`
- Pre-commit hooks: pint + eslint + prettier

---

### Task 1: Backup and update composer.json

**Files:**
- Modify: `composer.json`

**Steps:**

- [ ] **Step 1: Backup composer.json**
```bash
copy composer.json composer.json.bak
```

- [ ] **Step 2: Update php requirement**
Change `"php": "^8.2"` → `"php": "^8.3"`

- [ ] **Step 3: Update laravel/framework**
Change `"laravel/framework": "^12.0"` → `"laravel/framework": "^13.0"`

- [ ] **Step 4: Update phpunit (recommended by upgrade guide)**
Change `"phpunit/phpunit": "^11.0"` → `"phpunit/phpunit": "^12.0"`

- [ ] **Step 5: Commit**
```bash
git add composer.json
git commit -m "chore: prepare composer.json for Laravel 13"
```

---

### Task 2: Run composer update

**Steps:**

- [ ] **Step 1: Remove vendor and lock file for clean install**
```bash
rmdir /s /q vendor
del composer.lock
```

- [ ] **Step 2: Run composer update**
```bash
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe C:\laragon\bin\composer\composer.phar update
```

- [ ] **Step 3: Check for errors**
If errors: resolve package conflicts, check for incompatible packages.

- [ ] **Step 4: Verify Laravel version**
```bash
php artisan --version
```
Expected: `Laravel Framework 13.x.x`

---

### Task 3: Handle breaking changes

**Files:**
- Potentially: `config/cache.php`, `config/session.php`, `bootstrap/app.php`

**Steps:**

- [ ] **Step 1: Check if messagebag patch still applies**
The custom patch `patches/messagebag-arraypush-fix.patch` may conflict with Laravel 13. Check if Laravel 13 has fixed this issue. If fixed, remove the patch from composer.json `extra.patches`.

- [ ] **Step 2: Check for VerifyCsrfToken references**
Laravel 13 renames `VerifyCsrfToken` → `PreventRequestForgery`. If any references exist, update them.

- [ ] **Step 3: Check config/cache.php for serializable_classes**
Laravel 13 adds `serializable_classes` config. If not present, it defaults to `false` (secure).

- [ ] **Step 4: Check config/session.php**
Laravel 13 defaults session serialization to `json`. Keep as `php` to avoid invalidating sessions, OR change to `json` for better security (forces re-auth).

- [ ] **Step 5: Update laravel/ui if needed**
Check if `laravel/ui:^4.0` is compatible with Laravel 13. May need `^4.0` or `^5.0`.

---

### Task 4: Clear caches and test

**Steps:**

- [ ] **Step 1: Clear all caches**
```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

- [ ] **Step 2: Run migrations (if any)**
```bash
php artisan migrate
```

- [ ] **Step 3: Run tests**
```bash
php artisan test
```

- [ ] **Step 4: Manual smoke test**
Start dev server, verify key pages load (login, ecografías, configuración).

- [ ] **Step 5: Commit any config changes**
```bash
git add -A
git reset HEAD .env .env.example
git commit -m "chore: upgrade to Laravel 13"
```

---

### Task 5: Clean up

**Steps:**

- [ ] **Step 1: Remove backup**
```bash
del composer.json.bak
```

- [ ] **Step 2: Update memory**
Save upgrade completion to Engram memory.
