# Multi-Currency Operations (USD / ZWG / ZAR reality)

> Regional necessity, not a nice-to-have. Zimbabwean businesses trade primarily in USD with ZWG legally required alongside; SA operations run in ZAR; cross-border customers pay in BWP/ZMW/MZN. This doc defines how SparesPro handles it.

---

## Model

1. **One base currency per company** (set at company setup, immutable once transactions exist). Zimbabwe deployments: base = **USD**. SA deployments: base = **ZAR**.
2. All GL postings, stock values (AVCO), and reports are in base currency. Foreign amounts are converted at transaction-date rate and both are stored (`amount`, `currency_id`, `exchange_rate`, `base_amount`).
3. **Display currencies** (a setting): prices and totals can be *shown* in secondary currencies at the current rate — display only, never posted.

## Zimbabwe specifics

- **Dual pricing display**: a Configuration Centre toggle `currency.dual_display` renders every price/total on POS, quotes, invoices and shelf labels in both USD and ZWG at the day's rate (regulatory requirement has fluctuated — the toggle plus a per-document rate line keeps you compliant either way).
- **Receipts show**: rate used, both totals, and the currency actually tendered.
- **Multi-currency tender at POS**: one sale can be paid part-USD cash, part-ZWG swipe, part-ZAR cash. Each payment line carries its currency + rate; change is calculated in a configurable change currency (default USD).
- **Daily rate capture**: rates are entered manually each morning (admin.currencies) or via optional free API when internet allows — a POS setting can BLOCK sales if today's rate is missing, or fall back to last known rate with a warning banner.

## Rates

- `exchange_rates` table (already specced, Module 10.12): dated, append-only — historical documents always re-print with their original rate.
- Separate **buy/sell rate columns** (spread support) — tendering foreign cash uses buy rate; pricing display uses sell rate.
- Rate changes are audited; only `admin.currencies` permission can enter them.

## FX gains/losses

- Supplier invoices in foreign currency: liability recorded at invoice-date rate; payment at payment-date rate; difference posts automatically to the **FX Gain/Loss** GL account (mapped in Configuration Centre).
- Month-end: open foreign AP/AR balances revalued at closing rate (Phase 4.8 close checklist item).

## Cash management

- Each till/cash drawer holds **per-currency float counts**; X/Z-reads report per currency (USD cash, ZWG cash, ZAR cash, cards) and banking is per currency.
- Cash rounding rules per currency (e.g. USD to 5c when coin shortage — a setting).

## Testing focus (see testing-strategy.md)
- Split-tender across 3 currencies totals correctly to base.
- Historical invoice reprint uses original rate after 10 rate changes.
- FX variance posting on a USD-base ZAR supplier payment.
