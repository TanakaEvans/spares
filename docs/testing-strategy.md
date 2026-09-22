# Testing Strategy

> The definition of "done". A task in any [tasks file](tasks/README.md) may only be marked `[x]` when its automated tests pass AND its functional pass is performed. This document defines both.

---

## The Test Pyramid for SparesPro

```
        ┌───────────────┐
        │  Functional   │  Manual, scripted scenarios per feature —
        │  passes       │  the human check that it WORKS at the counter
        ├───────────────┤
        │  Feature      │  HTTP-level Laravel tests: routes, policies,
        │  tests        │  validation, Inertia props, full posting flows
        ├───────────────┤
        │  Unit tests   │  Services: StockLedgerService, GlPostingService,
        │  (the base)   │  SettingsService, price resolution, VAT math
        └───────────────┘
```

No browser-automation layer for v1 (Dusk/Playwright) — the cost/benefit at this team size favours disciplined feature tests + scripted functional passes. Revisit after go-live.

---

## 1. Unit tests (Pest/PHPUnit)

**What must have them:** every Service class, every money/VAT/discount calculation, AVCO recalculation, price resolution order, settings resolution order, exchange conversions, number sequence generation (incl. gaplessness under concurrency), ageing bucketing.

Rules:
- Pure logic gets pure tests — no DB where a value object will do; DB-backed service tests use `RefreshDatabase` + factories.
- Every business rule numbered in a module doc gets at least one test asserting the rule and one asserting its violation is rejected.
- Money assertions compare decimal strings, never floats.

## 2. Feature tests (per controller/flow)

**Minimum per sub-module** (mirrors the tasks-file Tests section):
- Happy path per route (index renders with expected Inertia props; store creates; posting flows post).
- AuthZ: an unauthorised role gets 403 on every route (loop the route list against a bare user).
- Validation: each Form Request's critical rules rejected with the right error keys.
- **The ledgers balance**: any test that posts a document asserts (a) stock ledger delta and stock level cache agree, (b) the GL journal balances, (c) sub-ledger = control account.

**Golden flow tests** (the crown jewels — full end-to-end in one test each, kept fast with factories):
| Test | Asserts |
|------|---------|
| `CashSaleFlowTest` | scan→sell→pay→post: stock ↓, revenue+VAT+COGS journal, receipt payload |
| `CreditSaleAndReceiptTest` | on-account sale, credit-limit block, receipt allocation, ageing |
| `ProcureToPayFlowTest` | PO→GRN→AVCO recalculated→3-way match→payment |
| `ReturnBothWaysTest` | customer credit note (stock in, VAT reversed) + supplier return |
| `JobCardToInvoiceTest` | job → parts issue → invoice, warranty lines excluded |
| `StockTakeVarianceTest` | freeze, blind count, variance journal |
| `MultiCurrencyTenderTest` | split USD/ZWG tender totals to base; FX variance on AP payment |
| `PeriodCloseTest` | posting into closed period rejected; reopen audited |
| `ConcurrencyTest` | two parallel sales of the last unit — one succeeds, one blocked; gapless invoice numbers under parallel posting |

## 3. Functional passes (manual, scripted)

Each tasks file lists 2–4 concrete scenarios per sub-module ("sell last unit from two tills simultaneously"). Executing them **on a real screen, with real hardware where relevant** is part of done — scanner scan, receipt print, drawer kick cannot be unit-tested.

- Record the pass in the tasks file when ticking the box: `[x] Functional pass — done 2026-09-30 (JT)`.
- UI acceptance: the screen complies with [ui-rules.md](design/ui-rules.md) — reviewer cites rule numbers for violations.

## 4. Regression & release gates

| Gate | When | What |
|------|------|------|
| CI on every PR | Always | Full unit + feature suite green; `pint --test`; `npm run build` |
| Phase exit | End of each implementation-plan phase | All golden flows green + functional passes in that phase's tasks files complete |
| Pre-go-live | Phase 7 | Full regression: all golden flows + hardware checklist + migration integrity checks + backup restore drill |
| Post-deploy smoke | Every production deploy | Script: login, POS loads, one draft sale created & voided, print test, integrity check green |

## 5. Test data

- Factories for every model (required with the model in tasks files).
- A `DemoSeeder` builds a small coherent world (2 branches, 30 parts across categories, 5 customers incl. one on-hold, 3 suppliers, open documents in every status) — used by tests needing a rich state and by demo/training environments. Never runs in production (guarded by environment check).

## 6. Ownership & discipline

- Tests live beside the module (`Modules/{X}/tests/`); the writer of a feature writes its tests in the same PR — no "test later" backlog.
- A bug found in production gets a failing test reproducing it **before** the fix is written.
- Flaky tests are fixed or deleted the week they flake — a red-ish suite trains people to ignore red.
