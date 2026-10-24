# Loading Your Data

Instead of typing thousands of parts by hand, load them from a spreadsheet. One sheet can carry your parts, prices, quantities, costs, bins and suppliers all at once.

Find it at [**System Administration → Data Upload**](route:imports.index).

## The golden rule

Every upload matches rows on their **number** (part number, supplier number, customer number). So if you upload a corrected sheet later, it **fixes** the existing records — it never makes duplicates. Upload as often as you like.

## Parts & Stock (do this one first)

This single sheet creates each part, prices it, loads its opening stock, puts it in a bin and links its supplier.

1. On the Data Upload page, find the **Parts & Stock** card and click **Download template**.
2. Open the template in Excel. Fill one row per part. The columns:
   - **Part Number** and **Description** are required.
   - **Cost Price (excl VAT)** — what you pay for it. Needed if you're loading a quantity.
   - **Selling Price** — fill either the incl-VAT or excl-VAT column, whichever you have.
   - **Qty On Hand** — how many you have right now.
   - **Bin**, **Reorder Point**, **Reorder Qty**, **Supplier** — optional but useful.
3. Save the file (Excel .xlsx or CSV both work).
4. Back on the Data Upload page, click **Upload Parts & Stock** and drop your file in.
5. The system shows you **which column maps to which field** — it guesses from the headings. Check them, fix any that are wrong, and click **Upload**.
6. You land on a **results page**: how many were created, updated, and skipped. Any skipped row is listed with the reason (for example "a cost price is needed to load 24 on hand") — fix those rows in your sheet and upload again.

## Post opening stock to the books

Loading quantities puts stock on the shelf. One more click records its **value** in the accounts so the books match the shelf.

1. On the Data Upload page, look at the **Opening stock** banner — it shows shelf value versus the Inventory account.
2. If they differ, click **Post opening stock to the books**. Done — System Health stays green.

## Suppliers and Customers

Same three steps (download template → fill in Excel → upload) for your **Suppliers** and **Customers**. Leave the number column blank and the system assigns one.

> You can also export any list (Customers, Parts, Suppliers, Stock) to Excel or PDF from its own screen — the **Export** button, top right.
