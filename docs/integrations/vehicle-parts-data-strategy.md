# Vehicle & Parts Data Strategy (optional paid path — NOT adopted)

> **Status: superseded for v1.** The adopted strategy is [local-data-seeding.md](local-data-seeding.md) — full local seeding and imports, zero recurring cost, zero runtime internet dependency. This document is retained as the reference for a *future* optional TECDOC upgrade if the business ever chooses to pay for it. The `ExternalCatalogSource` interface described below is still built — it just stays bound to `NullCatalogSource`.

---

## The Decision: Hybrid, Local-First with External Enrichment

We do **NOT** try to hold the entire world's automotive catalogue. We hold **what this business actually sells**, enriched on demand from external sources. The architecture is:

```
┌─────────────────────────────────────────────────────────────────┐
│                     SparesPro Local Database                     │
│   (the single source of truth for everything the business uses)  │
│                                                                  │
│   vehicle_makes ── vehicle_models ── vehicle_variants            │
│   parts ── part_fitments ── part_cross_references                │
└───────────▲──────────────▲──────────────▲──────────────▲────────┘
            │              │              │              │
   ① Seed imports  ② Supplier price  ③ TECDOC API   ④ Manual entry
   (CarQuery/NHTSA)   list imports    (on-demand +    (admin UI,
    free, one-time    (Excel/CSV,      cached, paid    always
    setup             ongoing)         subscription)   available)
```

### Why local-first?

1. **The POS cannot depend on the internet.** A counter sale must complete even when the external API is down or the connection is slow. All data needed to sell is local.
2. **The dataset is not actually "massive" for a database.** Even 500,000 parts × 2 KB/row ≈ 1 GB. 50,000 vehicle variants is nothing. MySQL handles this trivially with proper indexes. The "massive" problem is *acquiring and maintaining* the data, not storing it.
3. **You only need fitment data for parts you stock.** TECDOC has 100M+ records; a spares shop stocks 5,000–50,000 SKUs. Syncing only your SKUs keeps everything small and fast.

---

## The Four Data Inflows

### ① Seed Imports (free, at setup)

Artisan commands pull vehicle makes/models from free APIs once, at installation:

```bash
php artisan vehicles:import-makes      # CarQuery / NHTSA → vehicle_makes
php artisan vehicles:import-models     # per make → vehicle_models
```

- **CarQuery API** (carqueryapi.com): global makes, models, trims, years.
- **NHTSA vPIC** (vpic.nhtsa.dot.gov/api — free, no key): makes, models, and **VIN decoding**.
- Seeds ~30 priority makes for the African market first (Toyota, Nissan, Ford, VW, Isuzu, Mitsubishi, Haval, Chery…), then the long tail.

### ② Supplier Price List Imports (the main SKU source, ongoing)

The single most practical source of part numbers **is the supplier's own price list**. Every distributor already sends Excel/CSV catalogues with part numbers, descriptions, and prices.

- Import wizard (Module 6.2): upload → column mapping → auto-match via cross-references → review unmatched → activate.
- New parts are created automatically from unmatched rows (with buyer review).
- This is how the catalogue grows organically to exactly what the business can actually buy.

### ③ TECDOC API Integration (the external system — commercial, phase 2)

**Yes — we integrate with an external system: TECDOC (TecAlliance).** It is the global industry-standard parts catalogue (900+ makes, 100M+ part records, full fitment + cross-references), used by nearly every major parts retailer worldwide.

**Integration pattern: on-demand lookup with permanent local caching.**

```
Staff searches an unknown OEM number at POS
        │
        ▼
Local DB search ──── found? ──► use local record (fast path, 99% of lookups)
        │ miss
        ▼
TecDocCatalogSource->lookup(oemNumber)      ← API call
        │
        ▼
Result persisted into local DB:
  parts (if new) + part_cross_references + part_fitments
        │
        ▼
Next time: local hit. The DB grows with real usage.
```

Plus a **scheduled sync job** (weekly, queued) that refreshes fitment/cross-reference data *only for stocked parts* and pulls newly released vehicle models:

```bash
php artisan tecdoc:sync-fitments      # only for parts with stock or sales history
php artisan tecdoc:sync-vehicles      # new models/variants since last sync
```

**Code design — adapter interface, so the provider is swappable:**

```php
interface ExternalCatalogSource
{
    public function lookupByOemNumber(string $oem): ?CatalogResult;
    public function fitmentsForPart(string $partRef): Collection;
    public function newVehiclesSince(CarbonInterface $date): Collection;
}

class TecDocCatalogSource implements ExternalCatalogSource { ... }
class NullCatalogSource implements ExternalCatalogSource { ... }  // when no subscription
```

The system runs perfectly with `NullCatalogSource` (no subscription) — you just rely on inflows ①②④. TECDOC is an *enrichment*, not a dependency.

### ④ Manual Entry (always available)

Admin UI to add makes, models, variants, parts, fitments, and cross-references by hand — for grey imports, older vehicles, Chinese brands with weak catalogue coverage, and local-market variants. Manually entered fitment records are tagged `source = 'manual'` and never overwritten by sync jobs.

---

## Handling "Ever-Increasing" Growth

| Growth vector | Rate | How we absorb it |
|---------------|------|------------------|
| New car brands | A handful per year | Monthly `vehicles:import-makes` sync + manual add. A new brand is one row. |
| New models/variants | Hundreds per year | TECDOC/CarQuery scheduled sync; manual add on first customer request. |
| New part numbers | Thousands per year | Supplier price list imports (quarterly) + TECDOC on-demand lookup. |
| Supersessions | Continuous | `part_supersessions` chain; old number auto-redirects to new. |
| Fitment corrections | Continuous | Staff edit `manual` records; TECDOC records refresh on sync. |

### Storage & performance at scale

| Table | Realistic 5-yr size | Strategy |
|-------|--------------------|----------|
| `parts` | 50k–500k rows | Full-text index on part_number, oem_number, description. Trivial for MySQL. |
| `part_fitments` | 0.5M–5M rows | Composite index (part_id) and (make_id, model_id, year). Still small. |
| `part_cross_references` | 0.5M–3M rows | Index on reference_number. |
| `stock_ledger` | 1M+/yr | Append-only; archive rows > 3 years to `stock_ledger_archive`. |

**Search upgrade path:** MySQL FULLTEXT is fine to ~1M parts. If the catalogue grows beyond that or fuzzy search is needed ("sparkplug" → "spark plug"), add **Laravel Scout + Meilisearch** — a drop-in indexed search server, no schema changes required.

---

## VIN Decoding (free, live)

When a customer's vehicle is registered (Workshop / Customers module), the 17-character VIN is decoded via the **free NHTSA API** to auto-fill make, model, year, and engine:

```
GET https://vpic.nhtsa.dot.gov/api/vehicles/decodevin/{VIN}?format=json
```

Wrapped in a `VinDecoderService` with a local `vin_decode_cache` table so each VIN is only decoded once.

---

## Rollout Phases

| Phase | What | Cost |
|-------|------|------|
| **1 — Launch** | Seed makes/models (CarQuery/NHTSA), import all current supplier price lists, manual fitment entry for top 200 sellers | Free |
| **2 — Enrich** | NHTSA VIN decode on vehicle registration; monthly vehicle sync job | Free |
| **3 — Scale** | TECDOC subscription: on-demand lookups + weekly fitment sync for stocked parts | Subscription (contact TecAlliance) |
| **4 — Search** | Meilisearch via Laravel Scout if catalogue > ~1M parts | Free (self-hosted) |

---

## Related Docs
- [data-sources.md](../data-sources.md) — full catalogue of external sources, APIs and endpoints
- [Module 8: Vehicle & Parts Reference](../modules/08-vehicle-parts-reference.md) — the schema this strategy feeds
- [Module 6.2: Supplier Price Lists](../modules/06-supplier-management.md) — the import wizard
