# Data Migration & Go-Live Onboarding

> How an existing spares business — running on Excel sheets, a card system, or an old package — moves onto SparesPro without losing history or a day of trading. Phase 7 of the [implementation plan](../implementation-plan.md).

---

## What gets migrated (in dependency order)

| # | Data | Source reality | Into | Tool |
|---|------|---------------|------|------|
| 1 | Reference data | — | categories, brands, units, COA, VAT, terms | Bundled seed packs ([local-data-seeding.md](../integrations/local-data-seeding.md)) |
| 2 | Suppliers | Contact book / Excel | `suppliers` | Excel template import |
| 3 | Customers (trade) | Debtors book / Excel | `customers` | Excel template import |
| 4 | Parts catalogue | Stock sheets, supplier lists | `parts` + cross-refs | Excel template import |
| 5 | **Opening stock** | Physical count (see below) | `stock_ledger` OPENING_BALANCE | Stock-take module |
| 6 | Customer opening balances | Debtors ledger | AR opening invoices | Opening-balance import |
| 7 | Supplier opening balances | Creditors statements | AP opening invoices | Opening-balance import |
| 8 | Opening trial balance | Accountant | `gl_journals` opening journal | Manual journal |
| 9 | Customer vehicles (if workshop) | Job cards / memory | `customer_vehicles` | Import + capture-on-visit |

**Not migrated:** old transaction history (past invoices/jobs). It stays in the old system/files for reference; migrating years of transactions is where ERP projects go to die. History builds fresh from day one.

---

## The critical one: opening stock

**Do a real physical count — never trust the old system's numbers.** The migration IS a full stock take:

```
① Parts imported (step 4) with bins assigned
② Full stock take created in SparesPro (type: full, freeze irrelevant — not trading yet)
③ Physical count over a weekend (blind count sheets from the system)
④ Counted quantities entered
⑤ Unit costs: last purchase price per part (from supplier lists / invoices)
   — this becomes the opening AVCO
⑥ Post → OPENING_BALANCE ledger entries
   [GL] DR Inventory Asset  CR Opening Balance Equity
⑦ Inventory GL balance handed to the accountant for the opening TB (step 8)
```

## Opening balances (AR/AP)

Each unpaid customer invoice from the old system is captured as an **opening invoice** (document_type `invoice`, flagged `is_opening`, no stock lines — a single balance line) with its original date so ageing is truthful from day one. Same for supplier invoices. The opening journal (step 8) then balances: Debtors/Creditors control totals must equal the imported documents — the integrity check screen verifies this before go-live sign-off.

## Excel templates

One downloadable template per entity (`Suppliers.xlsx`, `Customers.xlsx`, `Parts.xlsx`, `OpeningBalances.xlsx`) with locked headers, example rows, and a Notes sheet. All go through the shared import wizard: preview → validation report → commit → undoable batch.

Common cleanups the validator catches: duplicate part numbers, customers appearing twice under spelling variants (fuzzy warning), missing categories (bulk-assign screen), phone/VAT format issues.

---

## Cutover plan (one weekend)

```
Fri close   Old system final Z-read; debtors/creditors lists printed & signed
Sat–Sun     Steps 4–8 above; hardware checklist (hardware-and-printing.md);
            users created, roles assigned; test sale + test GRN in a
            REHEARSAL database, then wiped
Mon 07:30   Integrity check green · first real sale on SparesPro
Week 1      Old system read-only; daily cash-up reconciled against
            expectations; snag list worked down
```

**Go/no-go checklist (all must be ✓):**
```
□ Stock count posted & spot-audited (10 random bins re-checked)
□ AR total = old debtors book (signed)      □ AP total = old creditors (signed)
□ Opening TB balances                        □ Receipt prints, drawer kicks
□ Cashiers completed a 10-sale practice run □ Backups running & restore-tested
```
