# Configuration Centre

> **Principle: if a value could ever differ between two businesses, two branches, or two years — it is a setting, not code.** The Configuration Centre is the single admin surface where every such value lives. No developer visit for a VAT change, a new till, a logo swap, or a discount threshold.

---

## Architecture

### Settings registry (code-defined, DB-stored)

Every setting is *declared* in code (so it's typed, validated, documented, and has a default) and *valued* in the database (so admins change it at runtime).

```php
// Each module ships a settings definition file: Modules/{X}/config/settings.php
return [
    'sales.max_discount_without_approval' => [
        'label'       => 'Maximum discount without supervisor approval (%)',
        'group'       => 'Sales › Discounts',
        'type'        => 'percent',          // string|int|decimal|percent|money|bool|select|json
        'default'     => 10,
        'rules'       => ['numeric', 'min:0', 'max:100'],
        'per_branch'  => true,               // ← can be overridden per branch
        'help'        => 'Above this, the POS asks for a supervisor PIN.',
    ],
    // ...
];
```

#### `settings` table
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| key | varchar(100) | `sales.max_discount_without_approval` |
| branch_id | bigint FK nullable | **null = global value; set = branch override** |
| value | text | Stored as string/JSON, cast by declared type |
| updated_by | bigint FK → users | |
| updated_at | timestamp | |

Unique on `(key, branch_id)`.

### Resolution order (multi-branch aware)
```
SettingsService::get('sales.max_discount_without_approval', $branch)
  1. branch-level row for this branch     ← wins if present
  2. global row (branch_id = null)
  3. declared default in code
```
Cached per request + invalidated on save. **Every module reads settings ONLY through `SettingsService`** — never `config()` for business values, never literals.

### Audit
Every change writes to the existing activity log: key, branch, old value, new value, user, timestamp. Settings screens require the `admin.settings.*` permission; some keys (financial thresholds) can be flagged `sensitive` → require super admin.

---

## The Configuration Centre UI

One screen: `admin.settings.index` — left rail of groups, search across all settings, branch selector at the top ("Editing: **Global** ▾ / Harare Main / Bulawayo"). Branch view shows each value with an "overridden / inherited from global" badge and a *Revert to global* action. Changed-but-unsaved values highlighted; Save per group.

---

## What must NEVER be hardcoded (the checklist)

Reviewers reject any PR containing literals for these:

| Area | Settings (examples) |
|------|--------------------|
| **Tax** | VAT rates & codes, VAT-inclusive vs exclusive display, tax authority name on documents |
| **Currency** | Base currency, display currencies, dual-currency receipt on/off, rate source, rounding rules, cash rounding step (e.g. nearest 5c) |
| **Documents** | Every number sequence (prefix/date format/padding/reset), every print template's header/footer text, terms & conditions text, default quote validity days |
| **Sales** | Discount approval threshold, price-override floor, credit-check rules (hold days, over-limit %), lay-by minimum deposit %, return window days, restock fee %, loyalty earn rate & expiry |
| **Purchasing** | PO approval value threshold(s), GRN over-receive tolerance %, invoice match tolerance (% and absolute), auto-requisition on reorder on/off |
| **Inventory** | Cost method (AVCO/FIFO), negative stock allowed (per branch), stock-take variance recount threshold, dead-stock definition (days), low-stock alert horizon |
| **Workshop** | Labour rates by type, scope-growth authorisation threshold, promised-time reminder minutes, comeback window days |
| **Finance** | Payment terms list, ageing bucket boundaries, statement day, FX variance account mapping, period lock behaviour |
| **Communication** | SMTP settings, SMS gateway settings, every email/SMS template, statement/report schedules and recipients |
| **UI** | Rows per page, date format, default landing page per role |
| **Category → GL mapping** | Sales/COGS account per part category — never a hardcoded account code in posting logic |

---

## Data Reusability: Document Identity Blocks

**Enter once, appear everywhere.** Company and branch details are captured in System Admin and *composed* into every outward-facing artefact — never re-typed per template.

```
                    ┌────────────────────────────┐
                    │  companies                 │  name, trading name, logo,
                    │  (one row)                 │  VAT no, tax no, banking
                    └───────────┬────────────────┘  details, footer text
                                │ merged with
                    ┌───────────▼────────────────┐
                    │  branches (per branch)     │  branch name, address,
                    │                            │  phone, email, branch logo
                    └───────────┬────────────────┘  (optional override)
                                │ rendered by
              ┌─────────────────┼──────────────────────┐
              ▼                 ▼                       ▼
     DocumentHeader        <x-doc-header/>       EmailLayout
     (PDF/Blade partial)   (thermal variant)     (mail header/footer)
              │                 │                       │
   invoices, quotes, POs,   80mm receipts        every outgoing email,
   GRNs, statements,        and till slips       statements, reports
   job cards, credit notes,
   delivery notes, labels
```

Rules:
1. **One partial** (`resources/views/print/partials/document-header.blade.php` + an 80mm variant) renders identity on ALL documents. A logo change is one upload; it propagates to every invoice, receipt, statement, email, and report cover instantly.
2. Branch fields override company fields where present (branch address on branch invoices); company banking details appear unless the branch has its own.
3. The same applies in-app: the sidebar brand block, login screen, and report covers read from the same `companies` row.
4. Statutory footer lines (VAT registration, "tax invoice" wording) are settings, composed into the same partial.

The same reuse principle applies beyond identity: customer addresses flow onto delivery notes, supplier banking flows onto payment runs, vehicle details flow from registry onto job cards and invoices — **no field is ever captured twice**.

---

## Multi-Branch Support (cross-cutting rules)

1. Every transactional table carries `branch_id` (already in all module schemas).
2. Every query is branch-scoped by default via a global Eloquent scope reading the user's active branch; "all branches" is an explicit permissioned filter, not the default.
3. Users have a home branch + optional additional branch grants; a branch switcher sits in the top bar (permission `switch-branch`).
4. Settings, number sequences, price lists, printers, and bin layouts are all per-branch-overridable (global fallback).
5. Reports aggregate across permitted branches only.
6. Inter-branch stock moves ONLY via stock transfers (1.2) — never by editing quantities.

---

## Build Notes

- Phase 0 item 0.1 in the [implementation plan](implementation-plan.md); tasks in [tasks/00-foundations.md](tasks/00-foundations.md).
- `SettingsService` is unit-tested for the 3-level resolution order and cache invalidation.
- Seeder loads all declared defaults so a fresh install is fully configured out of the box.
