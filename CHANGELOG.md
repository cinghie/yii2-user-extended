# Changelog — yii2-user-extended

Internal technical notes.

## 0.6.4

### Security

- `UserSearch`: parameterized `rule` filter query and validation against RBAC roles.
- Avatar upload: MIME/extension whitelist (jpg/png/webp), max size, block double-extension/executables, random rename, path confined under `avatarPath`.
- New module params: `avatarAllowedExtensions`, `avatarMaxSize`.
- Login brute-force: IP+username rate limit (`LoginRateLimiter`), temporary lock, progressive delay, captcha after N failures, generic anti-enumeration messages.
- Login module params: `enableLoginRateLimit`, `loginMaxAttempts`, `loginAttemptWindow`, `loginLockoutDuration`, `loginProgressiveDelay`, `loginDelayBaseSeconds`, `loginDelayMaxSeconds`, `loginCaptchaAfterAttempts`, `loginCaptchaAction`.
- User impersonation (`admin/switch`) disabled: AccessControl deny, `actionSwitch` → 403, `enableImpersonateUser = false`.

### CSRF / verbs

- `AdminController`: VerbFilter POST on `delete`, `deletemultiple`, `block`, `confirm`, `resend-password`, `activemultiple`, `deactivemultiple`, `switch`; sanitized bulk ids; no self-delete/block.
- `RoleController` / `PermissionController`: VerbFilter POST on `delete` (upstream RBAC ItemController lacked it).
- Login/register/settings/admin profile forms: CSRF via controller (default) + ActiveForm hidden field; do not use `enableCsrfValidation` on ActiveForm (not supported by kartik).
- User bulk AJAX (`UserSearch`): payload includes an explicit CSRF token.

### XSS output

- `SafeHtml` helper: encode, plain text, HtmlPurifier whitelist, safe http(s) URLs.
- Params: `signatureAllowHtml` (default `false`), `signatureAllowedHtml`.
- Profile: sanitize signature/bio/name/firstname/lastname/location/website on validation; `getSignatureHtml()` / `getBioHtml()` for output.
- Views: encode username/roles/avatar/alt/title; login flash encoded; `format => raw` only for static block/confirm icons.

### Password policy

- `PasswordPolicy` / `PasswordPolicyValidator`: min/max length, upper/lower/digit/special, ban common passwords.
- Params: `enablePasswordPolicy`, `passwordMinLength` (8), `passwordMaxLength` (72), `passwordRequire*`, `passwordBanCommon`, `passwordCommonList`, `passwordMaxAgeDays` (0 = off).
- Applied to `User`, `RegistrationForm`, `SettingsForm`, `RecoveryForm`.
- Migration `m260720_160000_add_password_changed_at_to_user`; `PasswordExpireFilter` redirects to `/user/settings/account`.
- Hash/verify only via `dektrium\user\helpers\Password` (Yii security).
- Review: `PasswordPolicy::generate()` used in create/register/resendPassword; `resendPassword` persists `password_changed_at`; `hasAttribute` guard if column missing; settings does not assign `password` when `new_password` is empty.

### Registration

- `BackendFilter`: 404 on `registration` and `recovery` controllers (profile/settings remain); attach on `user` with `as backend` or `userextended.blockRegistrationAndRecovery`.
- `RegistrationController` + `RegistrationRateLimiter` (IP + email, including confirmation resend).
- Params: `enableRegistrationRateLimit`, `registrationMaxAttempts`, `registrationAttemptWindow`, `registrationLockoutDuration`, `registrationProgressiveDelay`, `registrationDelay*`.
- Form protections already present: `captcha`, `terms`, Turnstile (`cloudflareTurnstileOnRegistration`); Dektrium confirmation via `enableConfirmation`.

### RBAC assignment

- `AdminController::actionAssignments` + `AssignmentController`: mutation POST-only, explicit CSRF, AccessControl `admin`.
- `Assignment` model (extends Dektrium): blocks self-escalation (`blockSelfRoleAssignment`); audit via `SecurityAudit` / logger; `user_id` not mass-assignable from POST.
- `Assignments` widget with `processPost=false` when the controller handles POST (no mutate-on-render).
- Params: `blockSelfRoleAssignment` (true), `enableRbacAssignmentAudit` (true).

### Session

- Session/auth timeout managed by the module (`sessionTimeout`, `useAbsoluteAuthTimeout`, `absoluteAuthTimeout`, `disableAutoLogin`).
- CRM default: `disableAutoLogin = true` (effective authTimeout; no remember-me).
- Session cookies: `hardenSessionCookies` applies HttpOnly + Secure (auto HTTPS/prod) + SameSite when missing (does not force `lifetime`).
- `components\WebUser`: on idle/absolute auth timeout, invalidate remember-me and do not re-login from cookie in the same request.
- Fix: `WebUser` registration updates DI only (preserves `identityClass`); no longer replaces the `user` component with a partial config; repair on `EVENT_BEFORE_REQUEST`.
- Session ID regeneration: Yii `switchIdentity` + `regenerateSessionId` on login; logout with `logout(true)`.
- Client redirect to login on expire (`SessionExpireAsset` / `session-expire.js`).
- Login flash with `?expired=1`.
- Fix: do not force `session.cookieParams.lifetime`; register session-expire asset only on non-AJAX HTML in `EVENT_BEGIN_PAGE` (avoids corrupted HTML on internal pages).

### Other

- Persist avatar filename via `updateAttributes` after upload (Admin/Settings).
- `SecurityController` uses the package `LoginForm`; `LoginForm` in the user `modelMap`.

### Admin user performance

- `UserSearch`: eager load `profile` + `roles`; role filter with parameterized `INNER JOIN`.
- `AuthAssignment` ActiveRecord; `getRolesHTML()` uses the relation (no concatenated SQL).
- Migration `m260720_111500_add_user_admin_indexes`: index on `user.last_login_at`.

### Caching

- `RbacRoleCache`: cache role list for admin filters + invalidate on role create/update/delete (`RoleController`).
- `ModuleConfig`: per-request memo of module settings (Profile, LoginRateLimiter, avatar).
- Params: `enableRbacRoleCache`, `rbacRoleCacheDuration`.

### Cloudflare Turnstile

- Optional widget on login (`login` / `login_prestashop`) and registration (dedicated flag).
- `TurnstileVerifier` (siteverify, fail closed); `TurnstileAsset` only when enabled.
- Params: `enableCloudflareTurnstile`, `cloudflareSiteKey`, `cloudflareSecretKey`, `cloudflareTurnstileTheme`, `cloudflareTurnstileOnRegistration` (default off; secret from `web-local`).
- Login fix: do not validate Turnstile in AJAX (one-time token); disable `enableAjaxValidation` when Turnstile is active; dedicated flash messages for captcha/Turnstile.
