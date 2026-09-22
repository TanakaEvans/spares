# Glossary

> One vocabulary for docs, UI, code and conversation. If a term is used differently anywhere, this file wins — fix the other place.

| Term | Meaning in SparesPro |
|------|---------------------|
| **AVCO** | Average cost method — stock valued at rolling weighted-average purchase cost. The v1 cost method |
| **Back-order** | A confirmed sales-order line for stock not yet on hand; fulfilled when a GRN arrives |
| **Bin / bin location** | A physical shelf address (`A-1-C-04`) inside a branch's warehouse |
| **Branch** | A physical trading location; every transaction, stock level and setting can be branch-scoped |
| **Comeback** | A vehicle returning within 30 days for the same fault — quality flag on the original job/technician |
| **Control account** | GL account whose balance must equal a sub-ledger total (Debtors, Creditors, Inventory); no direct posting |
| **Counter-document** | The correcting document for a posted one (credit note ↔ invoice, reversal ↔ journal, return ↔ GRN) |
| **Cross-reference** | Another catalogue's number for the same part (OEM ↔ aftermarket ↔ competitor ↔ EAN) |
| **Document identity block** | The shared company/branch header (logo, VAT no, banking) composed onto every printed/emailed document |
| **Fitment** | A record stating a part fits a vehicle make/model/variant/year(/engine) |
| **Flat rate** | Book labour time charged regardless of actual time taken |
| **Gapless sequence** | Document numbering with no missing numbers (legal requirement for tax invoices) |
| **Golden flow** | One of the nine end-to-end automated tests in [testing-strategy.md](testing-strategy.md) |
| **GRN** | Goods Received Note — the receiving document that brings supplier stock on hand |
| **Grey import** | JDM (Japanese domestic market) vehicle imported second-hand; differs from local-spec models |
| **Health check** | A registered integrity/operations assertion shown on [System Health](operations/system-health.md) |
| **Job card** | The workshop document for one vehicle visit: fault, labour, parts, costing |
| **Landed cost** | Product cost + freight + insurance + duty + clearing, allocated into unit cost on import GRNs |
| **Lay-by** | Deposit-based purchase; goods released only at full payment |
| **OEM number** | The vehicle manufacturer's own part number |
| **On hold** | Customer blocked from new credit sales (manual or auto at overdue/limit breach) |
| **Operational screen** | The keyboard-first page archetype (POS, GRN, stock-take count) |
| **Page guide** | The collapsible `F1` help block on every page ([spec](design/page-guide.md)) |
| **Posting / posted** | Writing a document irreversibly to the ledgers (stock and/or GL). Posted = immutable |
| **Price list** | A named pricing tier (retail/trade/wholesale/VIP/custom) assigned to customers |
| **Quarantine bin** | Non-sellable holding location for rejected/defective/returned-pending items |
| **Reorder point** | Stock level that triggers replenishment for a part at a branch |
| **RMA** | Return Merchandise Authorisation — the supplier's permission number for a return |
| **Seed pack** | A versioned CSV of reference data loaded by idempotent seeders ([local-data-seeding.md](integrations/local-data-seeding.md)) |
| **Settings registry** | Code-declared, DB-valued configuration with branch overrides ([configuration-centre.md](configuration-centre.md)) |
| **Stock ledger** | The append-only record of every stock movement; stock levels are its cached sums |
| **Sub-ledger** | Detail records behind a control account (AR invoices/receipts, AP invoices/payments, stock ledger) |
| **Supersession** | A part number replaced by a newer number; searches auto-follow the chain |
| **Suspend/recall** | Parking an in-progress POS sale and resuming it later |
| **3-way match** | PO qty/price ↔ GRN qty ↔ supplier invoice amount agreement before AP approval |
| **Variance** | Counted minus system quantity in a stock take (or cost difference in matching) |
| **X-read / Z-read** | Mid-shift till summary / end-of-day till close-out |
| **ZWG** | Zimbabwe Gold — Zimbabwean currency alongside USD ([multi-currency.md](operations/multi-currency.md)) |
