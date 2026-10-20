# About this page

The counter till. Scan or search a part, build the cart, choose the customer, then take payment. Posting creates a gapless **tax invoice**, moves stock at average cost, and books the sale to the ledger — all in one step.

## Fast flow
1. **Scan** a barcode or type a part number / name, then Enter to add.
2. Adjust **qty** and, if allowed, a **discount %** per line.
3. Change the **customer** if it's not a walk-in cash sale.
4. **Charge** → pick cash, card, EFT or account (or split), confirm the change, and complete.

## Keyboard shortcuts
| Key | Action |
|-----|--------|
| `Enter` in the scan box | Adds the exact part number / barcode typed or scanned (no need to pick from the list) |
| `F2` | Change customer |
| `F4` | Take payment |
| `F8` | Suspend the current sale (customer stepped away) |
| `F9` | Recall a suspended sale |
| `Esc` | Close the dialog |

Suspended sales hold **no stock** and live on this till only; prices and availability are re-checked on recall.

## Supervisor approval
A line discount above the configured limit needs a **supervisor's own username and password** in the payment dialog. The approver is recorded on the invoice and in the [Audit Trail](route:admin.audit-log.index). Supervisors are users whose role holds the `sales.pos.approve-discount` permission (Manager and Admin by default).

## Watch out
- A part with **no price** is flagged red and blocks the sale — set a price on the [default price list](route:sales.price-lists.index) first.
- Discounts above the configured limit need a supervisor's credentials (see above); the sale records who approved.
- **Account** payment is only for named customers within their credit limit and not on hold — walk-ins pay cash or card.

## Related
- [Tax Invoices](route:sales.invoices.index) · [Customers](route:customers.index) · [Price Lists](route:sales.price-lists.index)
