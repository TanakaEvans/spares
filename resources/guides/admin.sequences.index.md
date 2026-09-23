# About this page

Controls how every document number looks — invoices, quotes, purchase orders, job cards and more. The **preview** column shows exactly what the next number will be.

## How numbering works
- Format = prefix + optional date + counter, e.g. `INV-20260923-0001`.
- Numbers are **gapless**: if a document fails to save, its number is released — nothing is skipped. This matters for VAT audits.
- Yearly/monthly reset restarts the counter (e.g. job cards start at 00001 each year).

## Watch out
- The next-number counter itself is deliberately **not editable** — that protects the gapless guarantee.
- Change formats **before go-live**; changing the invoice format mid-year confuses filing.

## Related
- Documents themselves are configured in [Configuration Centre](route:admin.settings.index).
