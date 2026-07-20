# Changelog — yii2-user-extended

Note tecniche interne.

## 0.6.4

### Sicurezza

- `UserSearch`: filtro `rule` con query parametrizzata e validazione contro ruoli RBAC.
- Upload avatar: whitelist MIME/extension (jpg/png/webp), max size, blocco double-extension/eseguibili, rename random, path confinato sotto `avatarPath`.
- Nuovi parametri modulo: `avatarAllowedExtensions`, `avatarMaxSize`.
- Login brute-force: rate limit IP+username (`LoginRateLimiter`), lock temporaneo, delay progressivo, captcha dopo N fallimenti, messaggi generici anti-enumeration.
- Parametri modulo login: `enableLoginRateLimit`, `loginMaxAttempts`, `loginAttemptWindow`, `loginLockoutDuration`, `loginProgressiveDelay`, `loginDelayBaseSeconds`, `loginDelayMaxSeconds`, `loginCaptchaAfterAttempts`, `loginCaptchaAction`.
- Impersonificazione utente (`admin/switch`) disabilitata: AccessControl deny, `actionSwitch` → 403, `enableImpersonateUser = false`.

### CSRF / verbs

- `AdminController`: VerbFilter POST su `delete`, `deletemultiple`, `block`, `confirm`, `resend-password`, `activemultiple`, `deactivemultiple`, `switch`; ids bulk sanitizzati; no self-delete/block.
- `RoleController` / `PermissionController`: VerbFilter POST su `delete` (upstream RBAC ItemController non lo aveva).
- Form login/register/settings/admin profile/connect: `enableCsrfValidation => true`.
- Bulk AJAX utenti (`UserSearch`): payload include CSRF token esplicito.

### XSS output

- Helper `SafeHtml`: encode, plain text, HtmlPurifier whitelist, URL http(s) sicuri.
- Parametri: `signatureAllowHtml` (default `false`), `signatureAllowedHtml`.
- Profile: sanitizza signature/bio/name/firstname/lastname/location/website in validazione; `getSignatureHtml()` / `getBioHtml()` per output.
- Views: encode username/ruoli/avatar/alt/title; login flash encoded; `format => raw` solo per icone statiche block/confirm.

### Sessione

- Timeout sessione/auth gestito dal modulo (`sessionTimeout`, `useAbsoluteAuthTimeout`, `absoluteAuthTimeout`, `disableAutoLogin`).
- Default CRM: `disableAutoLogin = true` (authTimeout efficace; niente remember-me).
- Cookie sessione: `hardenSessionCookies` applica HttpOnly + Secure (auto HTTPS/prod) + SameSite se mancanti (non forza `lifetime`).
- `components\WebUser`: allo scadere di auth/absolute timeout invalida remember-me e non ri-loga dal cookie della stessa request.
- Fix: registrazione `WebUser` preserva `identityClass`/config Dektrium (evita `User::identityClass must be set` su login/`?expired=1`).
- Regenerazione session ID: Yii `switchIdentity` + `regenerateSessionId` su login; logout con `logout(true)`.
- Redirect client a login allo scadere (`SessionExpireAsset` / `session-expire.js`).
- Flash su login con `?expired=1`.
- Fix: non forzare `session.cookieParams.lifetime`; registra asset session-expire solo su HTML non-AJAX in `EVENT_BEGIN_PAGE` (evita HTML corrotto sulle pagine interne).

### Altro

- Persistenza filename avatar via `updateAttributes` dopo upload (Admin/Settings).
- `SecurityController` usa il `LoginForm` del package; `LoginForm` nel `modelMap` user.

### Performance admin utenti

- `UserSearch`: eager load `profile` + `roles`; filtro ruolo con `INNER JOIN` parametrizzato.
- `AuthAssignment` ActiveRecord; `getRolesHTML()` usa la relazione (niente SQL concatenato).
- Migration `m260720_111500_add_user_admin_indexes`: indice su `user.last_login_at`.

### Caching

- `RbacRoleCache`: cache lista ruoli per filtri admin + invalidate su create/update/delete ruolo (`RoleController`).
- `ModuleConfig`: memo per-request delle impostazioni modulo (Profile, LoginRateLimiter, avatar).
- Parametri: `enableRbacRoleCache`, `rbacRoleCacheDuration`.

### Cloudflare Turnstile

- Widget opzionale su login (`login` / `login_prestashop`) e registrazione (flag dedicato).
- `TurnstileVerifier` (siteverify, fail closed); `TurnstileAsset` solo se abilitato.
- Parametri: `enableCloudflareTurnstile`, `cloudflareSiteKey`, `cloudflareSecretKey`, `cloudflareTurnstileTheme`, `cloudflareTurnstileOnRegistration` (default off; secret da `web-local`).
- Fix login: non validare Turnstile in AJAX (token monouso); disabilita `enableAjaxValidation` quando Turnstile è attivo; flash dedicati per captcha/Turnstile.
