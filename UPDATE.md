# Update - yii2-user-extended

Optimization and security proposals for the module.  
Items marked ✅ have already been applied in the package.

## Security

1. **SQL injection in `UserSearch`** ✅
   - ~~The `rule` filter concatenated `$this->rule` into the SQL query.~~
   - Fix applied: parameterized query + `rule` validated against the RBAC role list.

2. **Avatar upload** ✅
   - ~~Restrict MIME/extension (jpg/png/webp), max size, safe file rename.~~
   - ~~Block executable uploads / double extensions.~~
   - ~~Sanitize/normalize the filename and ensure it stays under `avatarPath`.~~
   - Fix applied in `Profile::uploadAvatar` / `deleteImage` + module params `avatarAllowedExtensions` / `avatarMaxSize`.

3. **Admin access control** ✅
   - ~~Review `AdminController`: `switch` allowed for all authenticated users (`@`).~~
   - ~~Limit user switch to privileged roles and log every impersonation.~~
   - Decision applied: **switch/impersonation disabled** (deny access + `actionSwitch` 403 + `enableImpersonateUser = false`).

4. **Login / brute force** ✅
   - ~~Rate limit failed attempts (IP + username).~~
   - ~~Progressive delay or temporary account lock.~~
   - ~~Optional captcha after N failures.~~
   - ~~Generic error messages (already partially present): avoid username/email enumeration.~~
   - Fix applied: `LoginRateLimiter` + `LoginForm` / `SecurityController` (IP+login cache, lock, delay, optional captcha).

5. **Cloudflare Turnstile on login** ✅
   - ~~Add optional Cloudflare Turnstile widget support on the login form.~~
   - ~~Dedicated module params (disabled by default).~~
   - ~~Server-side siteverify validation; fail closed; secret only in web-local.~~
   - Fix: `TurnstileVerifier` + `TurnstileAsset` + widget on login/register; rate limit remains a second barrier.

6. **Session and authentication** ✅
   - ~~Safer defaults: consider `disableAutoLogin = true` in CRM environments.~~
   - ~~Session cookie: consistent `Secure` + `HttpOnly` + `SameSite` per environment.~~
   - ~~Regenerate session ID on login/logout.~~
   - ~~`absoluteAuthTimeout` option for max login duration.~~
   - ~~Invalidate remember-me cookie when `authTimeout` expires.~~
   - Fix: default `disableAutoLogin = true`; `hardenSessionCookies` / `sessionCookieSecure` / `sessionSameSite`; `absoluteAuthTimeout`; `WebUser` does not re-login from cookie after timeout; regenerate on login + logout.

7. **CSRF / verbs** ✅
   - ~~Ensure all mutating actions stay on POST + CSRF.~~
   - ~~Check login/register/settings forms and admin bulk actions.~~
   - Fix: VerbFilter POST on admin bulk/block/delete/confirm + role/permission delete; CSRF via controller + ActiveForm token (not an ActiveForm property); admin bulk AJAX includes CSRF token; no self-delete/block in bulk.

8. **XSS output** ✅
   - ~~Audit admin/profile/settings views: systematic encode of username, name, signature, bio.~~
   - ~~Avoid `format => raw` without sanitization.~~
   - ~~Signature/HTML editor: tag whitelist or plain-text storage.~~
   - Fix: `SafeHtml` helper; encode in admin/profile/settings/login; role names encoded; signature default plain text (`signatureAllowHtml=false`) + HtmlPurifier whitelist when enabled; bio always plain text.

9. **Password policy** ✅
   - ~~Configurable policy (min length, complexity, ban common passwords).~~
   - ~~Force periodic password change (module parameter).~~
   - ~~Hash only via Dektrium/Yii helpers (no unsafe custom comparisons).~~
   - Fix: `PasswordPolicy` + validator; `password_changed_at` migration; `PasswordExpireFilter`; register/settings/recovery/User forms; hash/verify only via `Password::hash` / `Password::validate`.
   - Review: `PasswordPolicy::generate()` for create/register/resend; `resendPassword` also saves `password_changed_at`; `hasAttribute` guard if migration not applied; User policy limited to password scenarios.

10. **Registration** ✅
    - ~~`BackendFilter` already blocks recovery/registration in some contexts: make it explicit and documented.~~
    - ~~If registration is enabled: captcha / Turnstile, terms, email confirmation, throttle.~~
    - Fix: `BackendFilter` documented (blocks only `registration`+`recovery`); CRM `user.as backend`; optional `blockRegistrationAndRecovery` via Bootstrap.
    - Fix: `RegistrationController` + `RegistrationRateLimiter` (IP+email); captcha/terms/Turnstile already on `RegistrationForm`; confirmation = `user.enableConfirmation`.

11. **Dependencies and packaging**
    - Align `composer.json` (Dektrium vs 2amigos/usuario) with the dependency actually used.
    - Update versions / security advisories.
    - Avoid abandoned dependencies; plan a modern auth migration if needed.

12. **Security logging**
    - Structured logs: login fail/success, block, delete user, role assign, switch user, session expire, Turnstile fail.
    - Do not log passwords or tokens in clear text.

13. **RBAC authorization** ✅
    - ~~Centralize role/permission assignment with CSRF + admin permission checks.~~
    - ~~Prevent self-escalation (an admin must not assign higher roles to themselves without audit).~~
    - Fix: `Assignment` model + `Assignments` widget; `AdminController::actionAssignments` and `AssignmentController` (POST/CSRF/admin); `blockSelfRoleAssignment`; `SecurityAudit` (logger or Yii::info).
    - Review: `user_id` not mass-assignable from POST (`safeAttributes` + rebind from URL in controllers).

## Optimizations

1. **Admin query performance** ✅
   - ~~Eager load `profile` / `roles` in the user grid (avoid N+1).~~
   - ~~DB indexes on `email`, `username`, `last_login_at`, `auth_assignment.user_id`.~~
   - ~~Replace string role subquery with join/param query.~~
   - Fix: `UserSearch` eager load + role filter join; `AuthAssignment` AR; `getRolesHTML` without concatenated SQL; `last_login_at` index migration (`username`/`email`/`auth_assignment.user_id` already upstream).

2. **Caching** ✅
   - ~~Cache RBAC role list (`getNameList`) with invalidate on change.~~
   - ~~Cache frequently used module config per request.~~
   - Fix: `RbacRoleCache` + invalidate from `RoleController`; `ModuleConfig` per-request memo.

3. **Client session expire**
   - Avoid intrusive `alert()`: non-blocking toast/banner.
   - Optional light heartbeat to align client/server timers.
   - Do not register JS assets on AJAX JSON responses.
   - Show warning once, configurable.

4. **Code / maintainability**
   - Align file header versions to `0.6.4`.
   - ~~Fix Profile scenario bug (`contact` incorrectly tied to `avatar` flag).~~ ✅ (fixed with `ModuleConfig` in scenarios/rules)
   - Type properties/methods and remove dead code / commented `var_dump`.
   - Extract common upload/session helpers into dedicated services.

5. **i18n**
   - Complete catalogs (`en` as well as `it`) for all new session/security messages.
   - Consistent keys and no hardcoded strings in views.

6. **Assets**
   - Publish assets with hash/versioning.
   - Minify session-expire JS in prod.
   - Robust `sourcePath` (path relative to the package, not only `@vendor/...`).

7. **Module configuration**
   - Document all parameters in README (`sessionTimeout`, captcha, Cloudflare Turnstile, avatar, etc.).
   - Validate parameter ranges in `Module::init()` (e.g. timeout >= 0; if Turnstile on → site/secret keys required).
   - Environment presets: `dev` / `prod` security defaults.

8. **Tests**
   - Unit/integration: login fail limit, session timeout, avatar upload reject, UserSearch rule injection, admin/switch access.
   - Smoke test redirect to login with `?expired=1`.
   - Turnstile tests: missing token, invalid token, valid token (mock siteverify).

## Suggested priorities

| Priority | Item |
|----------|------|
| ~~High~~ | ~~Fix SQL injection `UserSearch.rule`~~ ✅ |
| ~~High~~ | ~~Avatar upload hardening~~ ✅ |
| ~~High~~ | ~~User switch disabled~~ ✅ |
| ~~High~~ | ~~Login rate limit~~ ✅ |
| ~~Medium~~ | ~~Cloudflare Turnstile widget on login~~ ✅ |
| Medium | Stricter session defaults + parameter docs |
| ~~Medium~~ | ~~Eager loading / admin queries~~ ✅ |
| Medium | Session expire UX (no blocking alert) |
| Low | Version cleanup, en i18n, minify assets |

## Notes

- Changes should be made in the `vendor/cinghie/yii2-user-extended` package (or a fork) and then propagated to project environments.
- After security changes, update CHANGELOG/README and bump the module version.
