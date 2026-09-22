# Debugging & Error-Handling Standards

> How errors are raised, logged, surfaced, investigated, and permanently killed. Companion to [php-laravel.md](php-laravel.md) and [testing-strategy.md](../testing-strategy.md).

---

## 1. Exception architecture

One domain exception hierarchy per module, all extending a shared base:

```php
// app/Exceptions/DomainException.php  (shared base)
abstract class DomainException extends \RuntimeException
{
    public function userMessage(): string;   // safe, plain-language (UI rule §5 pattern)
    public function context(): array;        // structured facts for the log
}

// Modules/InventoryManagement/app/Exceptions/
InsufficientStockException      // part, branch, requested, available
StockFrozenException            // stock take in progress
// Modules/Sales/app/Exceptions/
CreditLimitExceededException    // customer, limit, balance, attempted
ClosedPeriodException           // period, attempted date
GaplessSequenceException        // sequence key
```

Rules:
1. Services **throw typed domain exceptions** — never return `false`/`null` for business failures, never throw bare `\Exception`.
2. A global renderer maps `DomainException` → Inertia error flash / 422 with `userMessage()`, and logs `context()`. Controllers do not catch domain exceptions except to add context.
3. Framework/unexpected exceptions render the generic error page — **never leak internals** (SQL, paths, traces) to users in production (`APP_DEBUG=false` verified by the deploy checklist).
4. Every `catch` block either rethrows, converts to a typed exception, or handles fully **and logs** — an empty catch is an automatic PR rejection.

## 2. Logging conventions

Structured context always; interpolated strings never:

```php
// ✅
Log::warning('grn.over_receipt_approved', [
    'grn' => $grn->grn_number, 'po_line' => $line->id,
    'ordered' => $line->qty_ordered, 'received' => $qty,
    'approved_by' => $supervisor->id, 'branch' => $branch->id,
]);
// ❌  Log::info("User approved over receipt for {$grn->grn_number}");
```

| Level | Use |
|-------|-----|
| `debug` | Dev-only tracing (stripped by prod LOG_LEVEL) |
| `info` | Notable business events not already in the audit log (imports started/finished, close run) |
| `warning` | Recovered anomalies: tolerance overrides, retry succeeded, fallback rate used |
| `error` | Failed operations needing attention: posting failed, delivery failed after retries |
| `critical` | Integrity in danger: unbalanced journal detected, backup failure |

- Message key = dot-notation `module.event` (grep-able); context carries IDs and numbers, never whole models, **never secrets/PANs**.
- Channels: `stack` (daily files, 30-day retention) + `critical` additionally routed to the Notifications Centre (backup-failed pattern, [10.13](../modules/10-system-administration/10.13-notifications-centre.md)).
- The business **audit trail is the activity log (10.6), not Laravel logs** — user-visible history goes there; logs are for operators/developers.

## 3. Dev-time tooling

| Tool | Use | Rule |
|------|-----|------|
| `laravel/telescope` (dev only) | Requests, queries, jobs, mails, exceptions | Gate to local env; never in prod composer `--no-dev` |
| Query log assertions | N+1 hunting | Feature tests may assert query counts on hot endpoints (`AssertDatabaseQueryCount` helper) |
| `APP_DEBUG=true` + Vite HMR | Local only | |
| React DevTools + Inertia page-prop inspection | Frontend state | Props are the state — debug data by inspecting the page props, not sprinkled `console.log` |
| Ray/dump | Local only | **No `dd()`/`dump()`/`console.log` reaches a commit** — CI greps and fails |

## 4. Production error visibility

- Unhandled exceptions log with full context; `critical`+`error` counts surface on the System Health screen ([operations/system-health.md](../operations/system-health.md)).
- Failed queued jobs land in `failed_jobs`, visible on System Health with one-click retry; a job failing all retries fires a Notifications Centre event to the administrator.
- Optional (setting): self-hosted error aggregation later; v1 = health screen + logs, keeping the zero-external-dependency stance.

## 5. The bug workflow (systematic, no loose ends)

```
① Reproduce      Write the failing test FIRST (testing-strategy §6) — feature test
                 for flows, unit test for calculations. No repro → instrument
                 (targeted warning logs) and ship the instrumentation.
② Root-cause     Fix the cause, not the symptom. A wrong number on screen is
                 usually a wrong posting — trace ledger → journal → service.
③ Fix            Smallest change that makes the failing test pass; run the
                 module suite + affected golden flows.
④ Sweep          grep for the same pattern elsewhere (a bug in one credit-note
                 VAT line usually lives in the supplier-credit twin too).
⑤ Record         fix(module): message referencing the symptom; if behaviour
                 was mis-specced, correct the sub-module doc in the same PR;
                 add a `[ ]→[x]` note if it touches a tasks item.
```

Data-repair rule: **never hand-edit posted data** (AGENTS.md §15). A data fix in production is a reviewed artisan command or a counter-document, committed, with before/after logged.

## 6. Integrity self-checks (bugs that announce themselves)

The invariants below are asserted continuously, not only at month-end:

| Invariant | Where enforced | On violation |
|-----------|---------------|--------------|
| Journal debits = credits | `GlPostingService` (throws pre-commit) | Transaction aborts |
| Stock level cache = ledger sum | Nightly job + on-demand check | `critical` log + notification |
| Sub-ledgers = control accounts | Nightly job + close checklist | 〃 |
| Gapless invoice sequence | Sequence service (row-locked) | Transaction aborts |
| Payment allocations ≤ receipt | DB check + service | Rejected |

Nightly results feed the System Health screen — a red invariant is a P1 bug by definition.
