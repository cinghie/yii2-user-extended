# Changelog — cinghie/yii2-user-extended

All notable changes to this package are documented in this file.

Format based on [Keep a Changelog](https://keepachangelog.com/).

### Documentation rules

- **`CHANGELOG.md` and `UPDATE.md` must always be written in English.**
- **`CHANGELOG.md` entries must use dated headings** (`## YYYY-MM-DD`, newest first), split by **commit days** — do **not** use `## [Unreleased]`.
- **`UPDATE.md` history must use dates** (`YYYY-MM-DD` headings, newest first) to record what changed or was decided over time.
- **`UPDATE.md` structure (mandatory):** (1) **Priority list first** — open items only, before explanations; (2) **Open items** with full detail; (3) **Processed** items kept lower and marked **Processed**, removed from the priority list; (4) History/ops after the roadmap.
- **Never include dangerous or sensitive references** in root or any `vendor/cinghie` `CHANGELOG.md` / `UPDATE.md`: credentials, secrets, private hosts, customer data, personal paths, exploit details, attack recipes, or proof-of-concept payloads.
- This is **especially mandatory for public packages** (this module may be published or shared): describe hardening only as features and safe configuration guidance.

---

## 2026-08-05

### Added

- Broader `SecurityAudit` coverage: settings `password_change` / `email_change` / `username_change` / `profile_update` / `email_confirm` / `network_disconnect` / `account_self_delete`; `registration_success`; `admin_profile_update`. Unit test `SecurityAuditTest` (sanitize + RBAC audit flag).
- `TurnstileVerifier::isActiveFormAjaxValidationRequest()` — detects Yii ActiveForm AJAX field validation via the `ajax` POST form id.
- Migration `m260805_163000_add_account_contact_to_profile` for optional profile `account` / `contact` integers.

### Fixed

- Registration Turnstile: `RegistrationController` always runs `performAjaxValidation` (do not skip it when Turnstile is enabled — that opened a forged-XHR registration path). `RegistrationForm` / `LoginForm` skip `siteverify` only for ActiveForm AJAX validation, not for every `isAjax` request. Registration views keep `enableAjaxValidation = false`. Covered by `RegistrationFormTurnstileAjaxTest`.
- Settings audit noise: `email_change` is not logged when the form still shows a pending `unconfirmed_email`; `email_confirm` is logged only when email or flags actually progress.
- Profile `account` field collided with social `getAccount()` relation (AJAX/load threw read-only property). Renamed relation to `getSocialAccount()` / `getSocialAccountAttributes()`; migration adds nullable `account` / `contact` columns; settings profile view no longer mis-renders birthday Select2 when `account` is enabled.

### Changed

- UPDATE roadmap: closed registration Turnstile AJAX token reuse and broader SecurityAudit coverage; residual backlog remains Dektrium RBAC assignment mutation centralization (plus 2FA / password history / ops). Consuming apps should Composer-refresh after releases — no ad-hoc production `vendor/` patches.

---

## 2026-07-23

### Fixed

- Automatic idle logout no longer stalls under Yii debug / Gii: `WebUser` skips renewing `__expire` for `debug`/`gii` requests, and `session-expire.js` ignores those AJAX URLs (common failure in Docker/dev with the debug toolbar polling).
- When `disableAutoLogin` is enabled, DI now sets `enableAutoLogin = false` (Dektrium bootstrap defaults to `true`).

---

## 2026-07-22

### Changed

- Documentation: English-only `CHANGELOG.md` / `UPDATE.md`, dated History, no dangerous/sensitive references (public-package safe wording).

---

## 2026-07-21

### Changed

- AdminLTE login views (`login`, `login_prestashop`) aligned with Bootstrap 4 / guest layout expectations.
- Module bootstrap handling for Bootstrap 4 login presentation.

---

## 2026-07-20

Version **0.6.4** hardening baseline (migrations dated `m260720_*`).

### Security

- `UserSearch`: parameterized `rule` filter and validation against existing RBAC roles.
- Avatar upload: MIME/extension allowlist (jpg/png/webp), max size, safer renaming, storage confined under `avatarPath`.
- New module params: `avatarAllowedExtensions`, `avatarMaxSize`.
- Login rate limiting (IP + username): temporary lock, progressive delay, captcha after repeated failures, generic failure messages.
- Login module params: `enableLoginRateLimit`, `loginMaxAttempts`, `loginAttemptWindow`, `loginLockoutDuration`, `loginProgressiveDelay`, `loginDelayBaseSeconds`, `loginDelayMaxSeconds`, `loginCaptchaAfterAttempts`, `loginCaptchaAction`.
- Optional DB-backed rate-limit store (`RateLimitStore` + migration `m260720_190000_create_userextended_rate_limit_table`); `cache` / `auto` backends retained.
- User impersonation (`admin/switch`) disabled by default: AccessControl deny, action returns 403, `enableImpersonateUser = false`.

### Security audit

- `SecurityAudit` helper: structured events via cinghie logger (if present) or `Yii::info` category `userextended.security`.
- Params: `enableSecurityAudit` (default `true`); RBAC events also gated by `enableRbacAssignmentAudit`.
- Event types include login success/fail, logout, captcha failures, block/unblock, delete, denied switch, session expire, RBAC assignment updates.
- Never logs passwords, tokens, auth keys, or CSRF values (`sanitizeData`).

### Dependencies / packaging

- Composer requires `dektrium/yii2-user` and `dektrium/yii2-rbac`; conflicts with `2amigos/yii2-usuario`.
- Security controls live in userextended overrides while Dektrium remains the base for now.
- Keep Yii2 core updated; medium-term migration to a maintained user module is still recommended.

### CSRF / verbs

- `AdminController`: VerbFilter POST on destructive/admin mutation actions; sanitized bulk ids; no self-delete/block.
- `RoleController` / `PermissionController`: VerbFilter POST on `delete`.
- Login/register/settings/admin profile forms: CSRF via controller defaults + ActiveForm hidden field.
- User bulk AJAX payloads include an explicit CSRF token.

### XSS-safe output

- `SafeHtml` helper: encode, plain text, HtmlPurifier allowlist, safe http(s) URLs.
- Params: `signatureAllowHtml` (default `false`), `signatureAllowedHtml`.
- Profile fields sanitized on validation; dedicated HTML getters for signature/bio.
- Views encode user-facing strings; `format => raw` limited to trusted static markup.

### Password policy

- `PasswordPolicy` / `PasswordPolicyValidator`: length bounds, character classes, optional common-password ban.
- Related module params for policy, max age, and bcrypt cost (`passwordHashCost`, default stronger than upstream).
- Applied to user/registration/settings/recovery forms; optional password-age redirect via `PasswordExpireFilter`.
- Hashing/verification via Dektrium `Password` helper (Yii security).
- Migration `m260720_160000_add_password_changed_at_to_user`.

### Registration / recovery

- Optional `BackendFilter` to disable public registration/recovery controllers when the product is backend-only.
- Registration rate limiting (IP + email), including confirmation resend paths.
- Recovery/registration hardening options: shorter confirmation windows, secure email-change strategy, no plaintext passwords in mail by default.
- Admin password reset sends mail before rotating credentials when configured that way.

### RBAC assignment

- Assignment mutations POST-only with explicit CSRF and `admin` AccessControl.
- Blocks self-escalation when `blockSelfRoleAssignment` is enabled; assignment audit optional.
- Assignments widget can disable mutate-on-render (`processPost=false`).

### Session

- Module-managed idle/absolute auth timeouts and optional remember-me disable.
- Session cookie hardening (`hardenSessionCookies`: HttpOnly, Secure when appropriate, SameSite).
- Custom `WebUser` behavior on auth timeout; session ID regeneration on login; logout with full destroy.
- Optional client warning/redirect assets for expired sessions; optional heartbeat endpoint.
- Assets published from package-relative paths with cache-busting.

### Other

- Shared `ProfileAvatarService` for admin/settings avatar updates.
- i18n catalogs for `en` / `it` (session/security/password/avatar).
- `ModuleSettings` validates/clamps params; optional `securityPreset` (`dev` / `prod` / `auto`).
- Optional Cloudflare Turnstile on login/registration (keys from app config; fail closed).
- PHPUnit coverage for rate limit, search filters, avatar reject, switch forbid, session expire flash, Turnstile mock, password policy, module settings, and Yii2 best-practice patterns.

### Admin performance / caching

- `UserSearch` eager loads profile/roles; parameterized role filter join.
- Migration `m260720_111500_add_user_admin_indexes` (e.g. `user.last_login_at`).
- `RbacRoleCache` for admin filters; per-request `ModuleConfig` memoization.
