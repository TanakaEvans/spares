# About this page

Print barcode labels so every part on the shelf can be **scanned at the till**. Build a batch, choose how you're printing, and open the labels as a PDF to print.

## Fill the batch
- **From a part** — *Print labels* on a part's page opens here with one label per unit on hand.
- **From a delivery** — *Print labels* on a goods received note gives one label per unit received. Label stock as it comes in.
- **Everything in stock** — *Add everything in stock* (optionally one category) adds every stocked part, one label per unit. Use this once at go-live to label the whole shop.
- **Scan or search** — type a part number, OEM number or description. `5*D3117` adds five labels.
- Change any **Labels** quantity, or *Set all to* **1 each** (shelf-edge labels) or **stock on hand**. A quantity of 0 skips a part.
- Your batch is kept on this computer until you clear it, so you can come back to it.

## What the barcode holds
- A part's own **EAN-13** if it has a valid one (*Barcode (EAN)* on the part).
- Otherwise the **part number** as a Code 128 barcode.

The till recognises both, so any label printed here rings the part up when scanned. The number is also printed under the bars in case a scan fails.

## Choosing a format
| Format | Use it for |
|---|---|
| **A4 sheet** | Sheets of sticky labels in an office printer. Click the first empty label on the little grid to **reuse a part-used sheet**. |
| **Label printer** | A thermal label printer — one label per page at the size in settings (default 50 × 30 mm). |
| **80mm receipt roll** | Your receipt printer, one label per slip. Handy until you have a label printer (adhesive 80mm rolls exist). |

The price printed is the **till price incl. VAT** — from the price list, or **cost + markup** (shown as *Auto*). Untick *Print the selling price* for labels without prices.

## Watch out
- **"Code too long for this label size"** — some long part numbers can't fit bars wide enough for the printer to print cleanly on a small label. Use the A4 sheet or receipt roll for those, or a wider label in settings.
- If A4 prints sit off the stickers, adjust **margin**, **labels across/down** in Configuration Centre › Labels to match the sheets you buy.
- Very large runs are split into parts so each PDF opens quickly — print each part in turn.
- Set the label printer's **resolution (dpi)** in settings; bars are sized to whole printer dots so they scan reliably.

## Related
- [Parts](route:inventory.parts.index) · [Stock Levels](route:inventory.stock.index) · [Goods Receiving](route:purchasing.grns.index) · [Point of Sale](route:sales.pos)
