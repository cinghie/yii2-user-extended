# Update - yii2-user-extended

Open hardening / dependency work only.  
Completed backlog (including DB-backed login/registration lockout) lives in `CHANGELOG.md` / `README.md`.

**Review (2026-07-20):** Re-audited applied work — no critical regressions. Fixed registration Turnstile AJAX token burn; widened `SecurityAudit` sanitization.

---

## 1. Dependencies and packaging (Dektrium)

**Status:** Dektrium **kept for now** (composer aligned to `dektrium/yii2-user` + `dektrium/yii2-rbac`; `conflict` on `2amigos/yii2-usuario`). CRM hardening sits in userextended, not upstream.

### Still open if staying on abandoned Dektrium

1. **2FA/TOTP for admins** — highest value for a CRM; not in Dektrium.
2. **Password history / no-reuse** — reject recently used passwords on change.
3. **Raise bcrypt `cost`** — Dektrium default `10` is weak by 2026 standards (prefer ≥12–13), without breaking existing hashes.
4. **If recovery/registration are ever re-enabled:** shorter token TTL, recovery throttle, email-change `STRATEGY_SECURE`, never email plaintext generated passwords.
5. **Keep social / `yii2-authclient` disabled** unless required (historical authclient CVEs).
6. **Optional ops hardening:** admin route IP allowlist; WAF / fail2ban on `/user/security/login`.
7. **Treat vendor Dektrium as a frozen fork:** review diffs before any Composer update; prefer path/VCS pin.
8. **Medium-term:** plan migration to a maintained fork (e.g. `cgsmith/yii2-user`) when feasible — not blocking day-to-day.

Also keep `yiisoft/yii2` patched (framework CVEs matter more than Dektrium itself).

---

## Suggested priorities

| Priority | Item |
|----------|------|
| High | 2FA/TOTP for admins |
| Medium | Password history / no-reuse |
| Medium | Raise bcrypt cost (≥12–13) |
| Medium | Harden recovery/registration paths before re-enabling them |
| Low | Admin IP allowlist / WAF; pin Dektrium; plan fork migration |

## Notes

- Implement in `vendor/cinghie/yii2-user-extended` (or a fork), then propagate to CRM environments.
- After security changes, update CHANGELOG/README and bump the module version.
