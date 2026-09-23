# AGENTS.md — SparesPro ERP

> Operating manual for AI agents (and humans) working in this repo. Read this first; it tells you which docs to read next, and the non-negotiable rules. **The docs are the spec — never invent behaviour that contradicts them; never write code for a feature before reading its sub-module doc.**

---

## What this project is

Motor spares (auto parts) ERP for Southern Africa (SA/Zimbabwe + region). **Laravel 12 + Inertia.js 2 + React 18 + Tailwind v4**, nwidart/laravel-modules architecture, MySQL. Multi-branch. Local-first: no paid external integrations, no runtime internet dependency for trading.

---

## Doc map — read in this order for any task

| You are about to… | Read (in order) |
|--------------------|-----------------|
| Do anything at all | This file → [docs/README.md](docs/README.md) (index) |
| Start a work session | [docs/implementation-plan.md](docs/implementation-plan.md) → the current phase's tasks file in [docs/tasks/](docs/tasks/README.md) |
| Build a feature | Its module overview `docs/modules/NN-*.md` → its sub-module spec `docs/modules/NN-*/N.n-*.md` (fields, rules, edge cases, screens) → the cross-module workflow it belongs to in [docs/workflows/](docs/workflows/README.md) |
| Write backend code | [docs/coding-standards/php-laravel.md](docs/coding-standards/php-laravel.md) + [database.md](docs/coding-standards/database.md) |
| Write frontend code | [docs/coding-standards/react-inertia.md](docs/coding-standards/react-inertia.md) + [docs/design/ui-rules.md](docs/design/ui-rules.md) + [component-standards.md](docs/design/component-standards.md) |
| Debug / handle errors | [docs/coding-standards/debugging-standards.md](docs/coding-standards/debugging-standards.md) |
| Unsure what a term means | [docs/glossary.md](docs/glossary.md) |
| Build a screen/menu | [docs/design/navigation-and-layout.md](docs/design/navigation-and-layout.md) + [module-menus.md](docs/design/module-menus.md) + [screen-designs.md](docs/design/screen-designs.md) |
| Touch settings/config | [docs/configuration-centre.md](docs/configuration-centre.md) |
| Touch seed/reference data | [docs/integrations/local-data-seeding.md](docs/integrations/local-data-seeding.md) |
| Write tests / finish a task | [docs/testing-strategy.md](docs/testing-strategy.md) |
| Currency, printing, backups, migration | [docs/operations/](docs/operations/) |

Don't bulk-read all 100+ docs — follow the table. Sub-module specs are self-contained (~150 lines each).

---

## The task workflow (mandatory)

```
implementation-plan.md  →  docs/tasks/NN-*.md  →  sub-module spec  →  code  →  tests  →  functional pass  →  tick the box
```

1. Work in the order of the current phase in [docs/implementation-plan.md](docs/implementation-plan.md). Don't jump phases without being asked.
2. Before starting a task: set it `[~]` in its tasks file.
3. A task is `[x]` ONLY when: code written per spec, automated tests pass, functional pass done ([testing-strategy.md](docs/testing-strategy.md)). Annotate: `[x] … — done YYYY-MM-DD`.
4. **Update the tasks file in the same commit as the work.** If reality diverged from the spec, update the sub-module doc too — docs must never lie.
5. New follow-up work discovered? Add it as a `[ ]` task in the right file — not a TODO comment in code.

---

## Non-negotiable rules

### Architecture
1. Business logic lives in **Services**; controllers are thin HTTP (validate → authorize → service → respond). Form Requests + Policies on every action.
2. **All stock movements go through `StockLedgerService`. All GL postings go through `GlPostingService`.** No direct writes to `stock_ledger`, `stock_levels`, `gl_journals` anywhere else. Both wrap `DB::transaction()` with `lockForUpdate()`.
3. Posted documents are immutable — corrections happen via counter-documents (credit note, reversal journal, supplier return).
4. Every module is an nwidart module under `Modules/`; base `app/` is shared infrastructure only.
5. New routes get `SystemRoute` permission seeds; route name = permission key (`module.resource.action`).

### Configuration over code
6. **No hardcoded business values.** VAT rates, thresholds, prefixes, terms, rates, templates, tolerances — everything on the checklist in [configuration-centre.md](docs/configuration-centre.md) is read via `SettingsService::get(key, branch)`. A literal business value in a PR is a rejection.
7. **Data is captured once, reused everywhere.** Company/branch identity (name, logo, VAT no, banking) renders through the shared document-header partials on every invoice/receipt/statement/email — never re-typed per template.

### Multi-branch
8. Every transactional table carries `branch_id`; queries are branch-scoped by default (global scope on the user's active branch); "all branches" is explicit and permissioned. Settings, sequences, price lists and printers support per-branch overrides with global fallback.

### Money & data
9. Money = `decimal(15,2)`, quantities `decimal(10,2)`, rates `decimal(15,6)` — never floats. Base-currency posting with per-transaction currency + rate stored ([multi-currency.md](docs/operations/multi-currency.md)).
10. Soft deletes on business entities; append-only ledgers; every override/approval audited (who, when, before/after).
11. Seeders are idempotent (`updateOrCreate` on natural keys); reference data comes from the CSV seed packs.

### Frontend
12. Sidebars are three-level: module cards (dashboard) → module sidebar ([module-menus.md](docs/design/module-menus.md)) → **per-sub-module contextual sidebar** ([docs/design/sidebars/](docs/design/sidebars/README.md)) showing ONLY that sub-module's screens plus curated Quick Links. Every sub-module gets its own sidebar built from its entry there; mixing another sub-module's items into WORK/INSIGHTS/SETUP fails review. Pages are one of the four archetypes (List/Form/Detail/Operational).
13. Follow [ui-rules.md](docs/design/ui-rules.md) — keyboard-first operational screens, scan-target focus management, disabled-with-reason over reject-after-click, drafts on operational documents, partial reloads, `useForm` for all forms, `route()` (Ziggy) for all URLs, shared components (`StatusBadge`, `DataTable`, `FormField`, `MoneyDisplay`, `ConfirmDialog`, `PageGuide`, `CommandPalette`) — never one-off variants.
13a. **Every page ships with a Page Guide** ([spec](docs/design/page-guide.md)) — a collapsible `F1` help block written from the sub-module spec; behaviour changes update the guide in the same PR.
13b. **Every rendered entity reference is a hyperlink** (UI-13): part numbers, customer names, document numbers, suppliers, technicians — all Inertia `<Link>`s with state-preserving Back. Plain-text entity references fail review.
13c. **Components follow [component-standards.md](docs/design/component-standards.md) exactly**: one primary button per view, Radix dialogs only (no stacking), no raw hex colours in components, and **one sidebar theme — every module sidebar inherits the main layout's dark theme via the shared `ModuleLayout`; `nav.js` configs carry content only, never styling.**

### Errors & debugging
13d. Services throw **typed domain exceptions** with `userMessage()` + `context()`; no empty catches, no `dd()`/`console.log` in commits, structured `module.event` log keys — [debugging-standards.md](docs/coding-standards/debugging-standards.md). New invariants register as health checks ([system-health.md](docs/operations/system-health.md)).

### Process
14. Conventional commits `type(scope): description` (scope = module), branches `feature|fix/module-desc` — see [git-workflow.md](docs/coding-standards/git-workflow.md). Tests in the same PR as the feature; a production bug gets a failing test before its fix.
15. Never `git push --force`, never skip hooks, never edit posted data in the DB by hand.

---

## Repo layout cheat-sheet

```
AGENTS.md                    ← you are here
docs/                        ← the full spec (start: docs/README.md)
  implementation-plan.md     ← build order + why
  tasks/                     ← living checklists (00-foundations … 10-system-admin)
  modules/NN-*.md + NN-*/    ← module overviews + per-sub-module specs
  workflows/  design/  operations/  coding-standards/  integrations/
  configuration-centre.md  testing-strategy.md  data-sources.md  architecture.md
app/                         ← shared infra only (User, Branch, middleware, SystemModule)
Modules/                     ← one nwidart module per business domain
resources/js/Layouts|Components/ ← shared shell + UI kit
database/data/               ← CSV seed packs (planned — see local-data-seeding.md)
```

## Current state (update this section as phases complete)

- **Built (pre-existing):** System Administration core (users, roles, company, branches, departments, auth flows).
- **Built (Phase 0, 2026-09-22):**
  - Dashboard with the 10 SparesPro module cards + sub-module card pages (`resources/js/modules.js`, `Pages/Modules/Show.jsx`) — 0.7 ✅
  - 0.1 Configuration Centre ✅ — `config/settings_registry.php` (30 settings), `SettingsService` (branch→global→default), `admin.settings.*`, full UI
  - 0.2 Currencies & Rates ✅ — `CurrencyService` (effective-dated buy/sell, base-relative), `CurrencySeeder`, `admin.currencies.*`, full UI
  - 0.3 Number Sequences ✅ — `NumberSequenceService` (gapless, row-locked), 20 seeded types, `admin.sequences.*`, full UI
  - 0.4 Document Identity ✅ — vat/banking fields, `DocumentIdentityService` (branch overlay), shared print partials (`resources/views/print/partials/`)
  - 0.5 Posting Engines ✅ — `GlPostingService` (balanced-only, period gating, control-account block, reversals) + `StockLedgerService` (AVCO, negative-stock setting, reservations, `verifyIntegrity()`); COA (44 accounts) + FY periods seeded; GL/stock tables migrated (parts FK deferred to Phase 1)
  - 0.6 Shared UI Kit ✅ — `ModuleLayout` with Level-2/Level-3 sidebar resolver (`resources/js/nav/system-admin.js` is the reference config); `DataTable`, `FormField`, `StatusBadge`, `SearchInput`, `MoneyDisplay`, `ConfirmDialog`, `EmptyState`, `FlashToasts` in `resources/js/Components/`
  - 0.8 Print/Email ✅ — DomPDF, `DocumentPdfService` (identity+settings into every print view), proof template, `DocumentMail` queued base mailable, `PrintButton`
  - 0.9 Page Guides ✅ — `resources/guides/{route}.md` + DB overrides via `PageGuideService`, shared automatically per route, `<PageGuide>` (`F1`), in-app docs viewer `/help/docs/{path}`
  - 0.10 Command Palette ✅ — `Ctrl+K` `<CommandPalette>`, `/search` endpoint, `config/search_pages.php` registry (entity sources plug in per module)
  - 0.11 Notifications ✅ core — `config/notification_events.php` catalogue, `NotificationRouterService` (bell/email/SMS-logged, branch-scoped, unrouted-logs), `<NotificationBell>` in both layouts; matrix/feed/delivery-log screens pending with admin-screens batch
  - Domain exceptions: `DomainException` base + MissingExchangeRate/UnbalancedJournal/ClosedPeriod/NoOpenPeriod/ControlAccountPosting/InsufficientStock
  - Test suite: 91 passing (sqlite :memory:)
- **Docs:** complete — all modules/sub-modules, workflows, design (incl. per-sub-module sidebars), operations, tasks.
- **PHASE 0 COMPLETE** (remaining stragglers tracked in tasks: SystemRoute permission seeds, page-guide/notification admin screens, legacy AdminLayout migration).
- **Next: Phase 1** — Vehicle Reference + Inventory master data: [docs/tasks/08-vehicle-reference.md](docs/tasks/08-vehicle-reference.md) then [docs/tasks/01-inventory.md](docs/tasks/01-inventory.md) per the [implementation plan](docs/implementation-plan.md).
