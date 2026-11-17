# About this page

The counter till. Find the part, build the sale, take payment, hand over the receipt and change — then straight on to the next customer without leaving the screen. Posting creates a gapless **tax invoice**, moves stock at average cost and books the sale to the ledger in one step.

## Fast flow
1. **Find** — scan a barcode, type a part number / OEM number, or type the vehicle and part type in any order (*"hilux brake pad"*, *"corolla water pump"*). With nothing typed, tap a **top seller** or **browse a category**.
2. Each result shows the **bin** to walk to, **stock here** (green / amber low / red out), the category and the **price incl. VAT**. Out-of-stock parts are greyed out; a part with no set price shows **Enter price** and can still be added.
3. **Enter** adds the highlighted part. Type `3*AF213` to add three at once.
4. Adjust **qty** with − / + (or the `+` `−` keys) and a **discount %** if allowed.
5. **Cash (F5)**, **Card (F6)** or **Account (F7)** — or **Split (F4)**. For cash, tap the note the customer handed over; the **change** shows in large digits. **Enter** completes the sale.
6. The **Sale complete** screen shows the change to give. **Enter** prints the 80mm receipt, **A** the A4 invoice, **N** or **Esc** goes to the next customer.

## Keyboard shortcuts
| Key | Action |
|-----|--------|
| `Enter` in the scan box | Add the exact part number / barcode (or the highlighted result) |
| `↑` `↓` | Move through results — or, with the scan box empty, through the sale lines |
| `+` `−` / `Del` | Change the selected line's qty / remove it (scan box empty) |
| `F2` | Change customer |
| `F3` | Clear the sale (asks first) |
| `F4` / `F5` / `F6` / `F7` | Split / Cash / Card / Account payment |
| `F8` / `F9` | Suspend / recall a sale |
| `F10` | Full-screen till (hides the menu) |
| `Esc` | Clear the search / close the dialog |

## Good to know
- **Changing the customer re-prices the sale** for their price list — changed lines are flagged.
- **Unfinished sales survive a refresh or power cut**: the cart is kept on this till and comes back when you reopen the page.
- **Suspended sales hold no stock** and live on this till only; prices and availability are re-checked on recall.
- **Superseded parts** show their replacement; one click adds the new number instead.
- The header shows **your sales today**; **Cash-up (X/Z)** opens the daily cash-up.

## Supervisor approval
A line discount above the configured limit needs a **supervisor's own username and password** in the payment dialog. The approver is recorded on the invoice and in the [Audit Trail](route:admin.audit-log.index). Supervisors are users whose role holds the `sales.pos.approve-discount` permission (Manager and Admin by default).

## How a price is worked out
For each part the till finds the price in this order:
1. **A price list** — the customer's list, then the default retail list. An explicit price here always wins.
2. **Automatic (cost + markup)** — if the part has no list price but a known purchase cost, the till prices it at **cost + the markup %**, then adds VAT. With the default **40%** markup, a part that cost R100 sells at **R140 excl / R161 incl** (15% VAT). These show a small **Auto** tag so you know the price follows the cost. Change the markup in **Configuration Centre › Sales › Default markup over cost**; it can be set per branch.
3. **Enter it yourself** — only when a part has **no cost at all** (nothing bought in yet). The cart shows a **No list price** box; type the price (never below cost). It's recorded against the invoice in the [Audit Trail](route:admin.audit-log.index).

## Watch out
- Most parts price **automatically** from their cost — load the cost (a supplier invoice / Data Upload / GRN) and the selling price follows. To pin a fixed price for a part, add it to the [default price list](route:sales.price-lists.index); that overrides the automatic one.
- A part with **no cost and no price** must be priced at the till before it can be sold, and never below cost.
- When negative stock is off (Configuration Centre › Inventory), the till won't sell more than is on hand here.
- **Account** payment is only for named customers within their credit limit and not on hold — walk-ins pay cash or card.

## Related
- [Tax Invoices](route:sales.invoices.index) · [Customers](route:customers.index) · [Price Lists](route:sales.price-lists.index) · [Daily Cash-up](route:reports.cashup)
