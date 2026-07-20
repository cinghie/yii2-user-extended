# Update - yii2-user-extended

Proposte di ottimizzazione e sicurezza del modulo.  
Le voci con ✅ sono già state applicate nel package.

## Sicurezza

1. **SQL injection in `UserSearch`** ✅
   - ~~Il filtro `rule` concatena `$this->rule` nella query SQL.~~
   - Fix applicato: query parametrizzata + validazione `rule` contro lista ruoli RBAC.

2. **Upload avatar** ✅
   - ~~Restringere MIME/extension (jpg/png/webp), max size, rename sicuro del file.~~
   - ~~Bloccare upload di file eseguibili / double extension.~~
   - ~~Sanitizzare/normalizzare il nome file e verificare che resti sotto `avatarPath`.~~
   - Fix applicato in `Profile::uploadAvatar` / `deleteImage` + parametri modulo `avatarAllowedExtensions` / `avatarMaxSize`.

3. **Access control admin** ✅
   - ~~Rivedere `AdminController`: azione `switch` consentita a tutti gli utenti autenticati (`@`).~~
   - ~~Limitare lo switch utente a ruoli privilegiati e loggare ogni impersonificazione.~~
   - Decisione applicata: **switch/impersonificazione disabilitata** (deny access + `actionSwitch` 403 + `enableImpersonateUser = false`).

4. **Login / brute force** ✅
   - ~~Rate limit su tentativi falliti (IP + username).~~
   - ~~Delay progressivo o lock temporaneo account.~~
   - ~~Opzione captcha dopo N fallimenti.~~
   - ~~Messaggi di errore generici (già parzialmente presenti): evitare enumeration username/email.~~
   - Fix applicato: `LoginRateLimiter` + `LoginForm` / `SecurityController` (cache IP+login, lock, delay, captcha opzionale).

5. **Cloudflare Turnstile sul login** ✅
   - ~~Aggiungere supporto opzionale al widget Cloudflare Turnstile nel form di login.~~
   - ~~Parametri modulo dedicati (default disabilitati).~~
   - ~~Validazione server-side siteverify; fail chiuso; secret solo in web-local.~~
   - Fix: `TurnstileVerifier` + `TurnstileAsset` + widget in login/register; rate limit resta seconda barriera.

6. **Sessione e autenticazione** ✅
   - ~~Default più sicuri: valutare `disableAutoLogin = true` in ambienti CRM.~~
   - ~~Cookie sessione: `Secure` + `HttpOnly` + `SameSite` coerenti per environment.~~
   - ~~Regenerare session ID al login/logout.~~
   - ~~Opzione `absoluteAuthTimeout` per durata massima login.~~
   - ~~Invalidare remember-me cookie allo scadere di `authTimeout`.~~
   - Fix: default `disableAutoLogin = true`; `hardenSessionCookies` / `sessionCookieSecure` / `sessionSameSite`; `absoluteAuthTimeout`; `WebUser` non ri-loga da cookie dopo timeout; regenerate su login + logout.

7. **CSRF / verbs** ✅
   - ~~Verificare che tutte le azioni mutative restino su POST + CSRF.~~
   - ~~Controllare form login/register/settings e azioni multiple admin.~~
   - Fix: VerbFilter POST su admin bulk/block/delete/confirm + role/permission delete; CSRF esplicito nei form login/register/settings/profile; bulk AJAX admin include token CSRF; no self-delete/block in bulk.

8. **XSS output** ✅
   - ~~Audit view admin/profile/settings: encode sistematico di username, nome, signature, bio.~~
   - ~~Evitare `format => raw` senza sanitizzazione.~~
   - ~~Signature/editor HTML: whitelist tag o salvataggio plain text.~~
   - Fix: `SafeHtml` helper; encode in admin/profile/settings/login; role names encoded; signature default plain text (`signatureAllowHtml=false`) + HtmlPurifier whitelist se abilitato; bio sempre plain text.

9. **Password policy**
   - Policy configurabile (lunghezza minima, complessità, ban password comuni).
   - Forzare cambio password periodico (parametro modulo).
   - Hash solo tramite helper Dektrium/Yii (nessun confronto custom unsafe).

10. **Registrazione**
    - BackendFilter già blocca recovery/registration in alcuni contesti: rendere esplicito e documentato.
    - Se registration abilitata: captcha / Turnstile, terms, email confirmation, throttle.

11. **Dipendenze e packaging**
    - Allineare `composer.json` (Dektrium vs 2amigos/usuario) alla dipendenza reale usata.
    - Aggiornare versioni / advisory di sicurezza.
    - Evitare dipendenze abbandonate; pianificare migrazione auth moderna se necessario.

12. **Logging sicurezza**
    - Log strutturati: login fail/success, block, delete user, role assign, switch user, session expire, Turnstile fail.
    - Non loggare password o token in chiaro.

13. **Autorizzazione RBAC**
    - Centralizzare assignment ruoli/permessi con check CSRF + permesso admin.
    - Impedire self-escalation (un admin non assegna a sé ruoli superiori senza audit).

## Ottimizzazioni

1. **Query e performance admin** ✅
   - ~~Eager loading `profile` / `roles` nella grid utenti (evitare N+1).~~
   - ~~Indici DB su `email`, `username`, `last_login_at`, `auth_assignment.user_id`.~~
   - ~~Sostituire subquery stringa ruoli con join/param query.~~
   - Fix: `UserSearch` eager load + join filtro ruolo; `AuthAssignment` AR; `getRolesHTML` senza SQL concatenato; migration indice `last_login_at` (`username`/`email`/`auth_assignment.user_id` già presenti upstream).

2. **Caching** ✅
   - ~~Cache lista ruoli RBAC (`getNameList`) con invalidate on change.~~
   - ~~Cache config modulo usate spesso in request.~~
   - Fix: `RbacRoleCache` + invalidate da `RoleController`; `ModuleConfig` memo per-request.

3. **Session expire client**
   - Evitare `alert()` invasivo: toast/banner non bloccante.
   - Heartbeat leggero opzionale per allineare timer client/server.
   - Non registrare asset JS su response AJAX JSON.
   - Warning una sola volta, configurabile.

4. **Codice / manutenibilità**
   - Allineare versioni file header a `0.6.4`.
   - ~~Correggere bug scenari Profile (`contact` legato per errore a flag `avatar`).~~ ✅ (fixato con `ModuleConfig` in scenarios/rules)
   - Tipizzare proprietà/metodi e rimuovere codice morto / `var_dump` commentati.
   - Estrarre helper comuni upload/session in servizi dedicati.

5. **i18n**
   - Completare cataloghi (`en` oltre a `it`) per tutti i nuovi messaggi sessione/security.
   - Chiavi coerenti e senza stringhe hardcoded nelle view.

6. **Asset**
   - Pubblicare asset con hash/versioning.
   - Minify JS session-expire in prod.
   - `sourcePath` robusto (path relativo al package, non solo `@vendor/...`).

7. **Configurazione modulo**
   - Documentare in README tutti i parametri (`sessionTimeout`, captcha, Cloudflare Turnstile, avatar, ecc.).
   - Validare range parametri in `Module::init()` (es. timeout >= 0; se Turnstile on → site/secret key obbligatori).
   - Preset environment: `dev` / `prod` security defaults.

8. **Test**
   - Unit/integration: login fail limit, session timeout, upload avatar reject, UserSearch rule injection, access admin/switch.
   - Smoke test redirect a login con `?expired=1`.
   - Test Turnstile: token mancante, token invalid, token valid (mock siteverify).

## Priorità suggerite

| Priorità | Voce |
|----------|------|
| ~~Alta~~ | ~~Fix SQL injection `UserSearch.rule`~~ ✅ |
| ~~Alta~~ | ~~Hardening upload avatar~~ ✅ |
| ~~Alta~~ | ~~Switch utente disabilitato~~ ✅ |
| ~~Alta~~ | ~~Rate limit login~~ ✅ |
| ~~Media~~ | ~~Widget Cloudflare Turnstile sul login~~ ✅ |
| Media | Default sessione più strict + docs parametri |
| ~~Media~~ | ~~Eager loading / query admin~~ ✅ |
| Media | UX session expire (no alert bloccante) |
| Bassa | Cleanup versioni, i18n en, minify asset |

## Note

- Interventi da fare sul package `vendor/cinghie/yii2-user-extended` (o fork) e poi propagare agli environment del progetto.
- Dopo le modifiche di sicurezza, aggiornare CHANGELOG/README e bump versione modulo.
