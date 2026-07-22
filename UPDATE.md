# Update — cinghie/yii2-user-extended

Open hardening / dependency work.  
Completed package detail also lives in `CHANGELOG.md` / `README.md`.

### Documentation rules (root + all `vendor/cinghie`)

- **`CHANGELOG.md` and `UPDATE.md` must always be written in English.**
- **`CHANGELOG.md` entries must use dated headings** (`## YYYY-MM-DD`, newest first), split by **commit days** — do **not** use `## [Unreleased]`.
- **`UPDATE.md` history must use dates** (`YYYY-MM-DD` headings, newest first) to record what changed or was decided over time.
- **`UPDATE.md` structure (mandatory):**
  1. **Priority list first** — only items still open, before any detailed explanation.
  2. **Open items next** — full sections for suggestions still to process.
  3. **Processed items lower** — keep processed suggestions in the file, clearly marked **Processed** (with date when useful), placed below open items; remove them from the priority list.
  4. **History / notes** — dated narrative after the roadmap.
- **Never include dangerous or sensitive references** in root or any `vendor/cinghie` `CHANGELOG.md` / `UPDATE.md`: credentials, secrets, private hosts, customer data, personal paths, exploit details, attack recipes, or proof-of-concept payloads.
- This is **especially mandatory for public packages** (this module may be published or shared): describe hardening only as features and safe configuration guidance.

---

## Priority list

| Priority | Item |
|----------|------|
| High | 2FA/TOTP for admins |
| Medium | Password history / no-reuse |
| Low | Deploy network restrictions for admin entry |
| Low | Pin/fork strategy for upstream user packages |
| Low | Plan migration to a maintained user module |

---

## Open — Dependencies and packaging (Dektrium)

**Status:** Dektrium **kept for now** (`dektrium/yii2-user` + `dektrium/yii2-rbac`; conflict on `2amigos/yii2-usuario`). Product hardening lives in userextended overrides, not upstream.

### Still open if staying on Dektrium

1. **2FA/TOTP for admins** — highest value for CRM-style backends.
2. **Password history / no-reuse** — reject recently used passwords on change.
3. **Ops hardening (deploy-specific):** restrict admin entry points by network policy where appropriate.
4. **Treat upstream user packages carefully:** review diffs before Composer updates; prefer path/VCS pins for forks.
5. **Medium-term:** plan migration to a maintained user module when feasible.

Also keep `yiisoft/yii2` updated (framework patches matter more than abandoned user bases).

### Notes

- Implement in this package (or a maintained fork), then release via Composer.
- After security changes, update CHANGELOG/README and bump the module version.

---

## Processed

> Keep processed suggestions here (not in the Priority list). Newest processed first.

### 2026-07-22 — Processed

| Item | Outcome |
|------|---------|
| Soften public UPDATE/CHANGELOG wording; annotate English + safety + Priority/Processed structure | **Processed** — documentation rules applied. |

### 2026-07-21 — Processed

| Item | Outcome |
|------|---------|
| AdminLTE login views / Bootstrap 4 module bootstrap alignment | **Processed** — see `CHANGELOG.md` for `2026-07-21`. |

### 2026-07-20 — Processed

| Item | Outcome |
|------|---------|
| Keep Dektrium for now; harden in userextended | **Processed** — decision recorded; Composer aligned. |
| Keep optional social login disabled unless required | **Processed** — standing product policy. |
| Re-audit applied hardening; Turnstile AJAX token handling; SecurityAudit sanitization | **Processed** — no critical regressions noted. |
| 0.6.4 hardening baseline (rate limits, password policy, CSRF/verbs, session, RBAC, SafeHtml, tests, …) | **Processed** — see `CHANGELOG.md` for `2026-07-20`. |

---

## History

### 2026-07-22

- Priority list moved to top; processed items moved below open work and removed from priorities.

### 2026-07-20

- Dektrium kept; open backlog remains 2FA, password history, deploy restrictions, fork/migration plan.
