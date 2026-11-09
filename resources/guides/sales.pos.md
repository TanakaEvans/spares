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

## Pricing a part at the till
Some parts arrive with a cost but no selling price yet (for example stock loaded from a supplier invoice). When you add one, the cart shows a **No list price** badge and a **Selling price (incl.)** box with the part's **cost** beside it. Type the price you're charging and carry on — the sale won't let you charge **below cost**, and the manually-entered price is recorded against the invoice in the [Audit Trail](route:admin.audit-log.index). Set a proper price on the [default price list](route:sales.price-lists.index) (or via Data Upload) so it fills in automatically next time.

## Watch out
- A part with **no price** can still be sold by entering a price at the till — but never below cost. For anything you sell often, set a price on the [default price list](route:sales.price-lists.index) or load prices via Data Upload.
- When negative stock is off (Configuration Centre › Inventory), the till won't sell more than is on hand here.
- **Account** payment is only for named customers within their credit limit and not on hold — walk-ins pay cash or card.

## Related
- [Tax Invoices](route:sales.invoices.index) · [Customers](route:customers.index) · [Price Lists](route:sales.price-lists.index) · [Daily Cash-up](route:reports.cashup)
