# Stock & Parts

Find any part, see where it lives and how much you have, and keep the numbers honest with adjustments, transfers and stock takes.

## Find a part

**Inventory → Parts Catalogue** (route:inventory.parts.index). Search by part number, OEM number, barcode, description, or even a cross-reference. Click a part number to open its **hub**.

## The part hub

One page with everything about a part, all linked:

- **Stock per branch** and the **bin** it sits in.
- **Suppliers** who stock it (preferred one first) and their cost.
- **Fits these vehicles** — the makes and models it's listed against.
- **Cross-references** and **supersession** — equivalents and replacements.
- **Recent movements** — the last stock-ledger entries, each linked back to the sale, GRN, adjustment, transfer or job that caused it.

## Check stock levels

**Inventory → Stock Levels** (route:inventory.stock.index): on-hand, reserved, available and value for every part, filterable by branch. Click a bin code to see what else is in that bin.

## Adjust / write off stock

When a count is wrong, or stock is damaged or lost:

1. **Inventory → Adjustments → New** (route:inventory.adjustments.index).
2. Pick the part(s), the new or delta quantity, and a **reason**.
3. Post it. The difference is written to a write-off account so the books stay true.

## Transfer between branches

1. **Inventory → Transfers → New** (route:inventory.transfers.index).
2. Choose the **from** and **to** branch and the parts.
3. **Dispatch** from the source; the receiving branch **receives** it in. Value moves with the stock.

## Do a stock take

1. **Inventory → Stock Takes → New** (route:inventory.stock-takes.index) — this snapshots current levels.
2. Count the shelf and key in the **counted** quantity for each part.
3. **Review** the variances, then **Post**. One adjustment corrects everything to match the count.

## Bins and reference data

- **Bins** (route:inventory.bins.index) — your shelf locations.
- **Categories** (route:inventory.categories.index) and **Brands** (route:inventory.brands.index) — how parts are grouped.
- **Serial & batch tracking** (route:inventory.serials.index) for parts that need it.
