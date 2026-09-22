# Data Sources: Car Brands, Part Numbers & Reference Data

> Reference catalogue of where vehicle/parts data *can* come from. **The adopted v1 strategy is [integrations/local-data-seeding.md](integrations/local-data-seeding.md)** — free sources, bundled seed packs, supplier imports. Paid options below (TECDOC etc.) are documented for future reference only and are NOT part of the build.

---

## The Core Problem

A motor spares system needs three categories of reference data:

| Category | What it is | Volume |
|----------|-----------|--------|
| **Vehicle database** | Makes, models, variants, years, engine codes | ~50,000 variants |
| **Parts catalogue** | Part numbers, descriptions, categories, barcodes | 10,000–500,000 SKUs |
| **Fitment data** | Which parts fit which vehicles | Millions of records |

Building this from scratch is impractical. The strategy is: **source externally where possible, import from suppliers, manually fill gaps.**

---

## Vehicle Makes & Models

### Option 1: TECDOC (Recommended for Production)

**What it is:** The global automotive parts catalogue standard. Used by AutoZone, O'Reilly, Europarts, and most major retailers worldwide.

| Attribute | Details |
|-----------|---------|
| Provider | TecAlliance GmbH (Germany) |
| Coverage | 900+ vehicle makes, 100M+ part records, 200+ countries |
| Fitment data | Yes — complete OEM and aftermarket fitment |
| Cross-references | Yes — OEM ↔ 70,000+ aftermarket brands |
| Images | Yes — product images included |
| African market | Strong coverage of all imported makes (Toyota, Nissan, Ford, VW, etc.) |
| Integration | REST API or full database download |
| Cost | Subscription — contact tecdoc.net for pricing |
| Data format | JSON API or proprietary database format |

**Integration steps:**
1. Sign up at [tecdoc.net](https://www.tecdoc.net)
2. Receive API credentials
3. Build a `TecDocClient` service class in Laravel
4. Import vehicle makes/models/variants via scheduled job
5. Import fitment data for stocked parts
6. Cache results — don't query TECDOC on every page load

**API example (pseudocode):**
```
GET /articles?searchType=OEM&searchQuery=90915-YZZD3
→ Returns: Part details + vehicle fitments + cross-references

GET /vehicles/makes
→ Returns: All makes

GET /vehicles/models?makeId=16
→ Returns: All Toyota models
```

---

### Option 2: CarQuery API (Free — Development / Small Scale)

**What it is:** Free JSON API for vehicle makes, models, and specifications.

| Attribute | Details |
|-----------|---------|
| Provider | carqueryapi.com |
| Coverage | Most global makes, models, years 1941–present |
| Fitment data | No — vehicle specs only |
| Cost | Free (non-commercial) / Pro plan for commercial |
| Rate limit | Low — not suitable for bulk import |

**API Examples:**
```
# Get all makes
http://www.carqueryapi.com/api/0.3/?cmd=getMakes

# Get models for a make
http://www.carqueryapi.com/api/0.3/?cmd=getModels&make=toyota

# Get trims/variants for a model
http://www.carqueryapi.com/api/0.3/?cmd=getTrims&model=corolla&year=2020
```

**Use case:** Seed the `vehicle_makes` and `vehicle_models` tables during development. Replace with TECDOC data in production.

**Sample Laravel import command:**
```php
class ImportVehicleMakesFromCarQuery extends Command
{
    protected $signature = 'import:vehicle-makes';

    public function handle(Http $http): void
    {
        $response = $http->get('http://www.carqueryapi.com/api/0.3/', [
            'cmd' => 'getMakes',
        ]);

        foreach ($response->json('Makes') as $make) {
            VehicleMake::updateOrCreate(
                ['name' => $make['make_display']],
                ['country_of_origin' => $make['make_country'] ?? null]
            );
        }
    }
}
```

---

### Option 3: NHTSA Vehicle API (Free — US Focus)

**What it is:** US government database of all vehicles sold or registered in the USA.

| Attribute | Details |
|-----------|---------|
| Provider | National Highway Traffic Safety Administration (US Govt) |
| Coverage | All vehicles registered/sold in the USA, 1981+ |
| Cost | Free — no API key required |
| Base URL | `https://vpic.nhtsa.dot.gov/api/` |

**Useful endpoints:**
```
# All makes
GET https://vpic.nhtsa.dot.gov/api/vehicles/getallmakes?format=json

# Models for a make
GET https://vpic.nhtsa.dot.gov/api/vehicles/getmodelsformake/toyota?format=json

# VIN decode (17-char VIN → full vehicle spec)
GET https://vpic.nhtsa.dot.gov/api/vehicles/decodevin/1HGBH41JXMN109186?format=json
```

**VIN decode is particularly valuable:** When a customer registers their vehicle, decode the VIN to auto-populate make, model, year, engine code.

---

### Option 4: Open Source / Community

| Source | URL | Notes |
|--------|-----|-------|
| Wikidata vehicle models | wikidata.org | Free, community-maintained, SPARQL API |
| openautomotive.org | openautomotive.org | Open vehicle database, limited |
| vpic (NHTSA) | vpic.nhtsa.dot.gov | Best free US source |
| fueleconomy.gov | fueleconomy.gov/feg/ws | US fuel economy data + make/model |

---

### Recommended Seeding Strategy

**Phase 1 (Development):** Use CarQuery API to seed basic makes and models.

**Phase 2 (Pre-launch):** Import core 25–30 makes from NHTSA or manual entry, focused on market-specific popular makes.

**Phase 3 (Production):** Integrate TECDOC for full fitment data and cross-references.

**Phase 4 (Ongoing):** Allow staff to add makes/models/variants manually for vehicles not in the database.

---

## Parts Data (SKUs & Part Numbers)

### Primary Source: Supplier Price Lists

The most practical source of part data for most spares businesses is their **existing supplier price lists**. Every supplier sends a price list (Excel, CSV, or PDF) that contains:
- Their part numbers
- Descriptions
- Categories
- Prices

**Import workflow (Module 3: Purchasing → Supplier Price Lists):**
```
1. Supplier emails Excel/CSV price list
2. Upload in SparesPro via Supplier Price Lists → Import
3. Column mapping wizard maps their columns to system fields:
   - Their "Part No" → our "supplier_part_number"
   - Their "Description" → our "description"  
   - Their "Price" → our "cost_price"
4. Auto-match to existing parts via cross-reference table
5. Unmatched rows: create new parts or flag for review
6. Activate the price list
```

**Import file requirements:**
- Support `.xlsx`, `.xls`, `.csv`
- First row = headers
- Use `maatwebsite/laravel-excel` for import processing
- Handle duplicate part numbers (update vs create)

---

### OEM Part Numbers

**Per manufacturer, free sources:**

| Manufacturer | Source | Notes |
|-------------|--------|-------|
| Toyota | Toyota Genuine Parts website | Search by model/VIN |
| Ford | Ford Parts (parts.ford.com) | US-focused |
| VW/Audi | ETKA (subscription) or VW Parts website | |
| BMW | BMW ETK (subscription) or Realoem.com | realoem.com is free |
| Mercedes | Mercedes STAR Parts (subscription) | |

**For African market:** Distributors often provide OEM catalogues:
- Toyota Zimbabwe / Toyota South Africa
- Nissan Africa
- Ford Motor Company of Southern Africa

---

### Aftermarket Parts Databases

Major aftermarket brands publish their own catalogues:

| Brand | Parts | Free Access |
|-------|-------|------------|
| **Bosch** | Filters, spark plugs, sensors, starters, alternators | Bosch Automotive Catalog app |
| **NGK** | Spark plugs, glow plugs, sensors | ngkntk.com/apps |
| **Denso** | Spark plugs, O2 sensors, starters | denso-am.com |
| **Mann+Hummel** | Filters (Mann, WIX, Purolator) | mann-filter.com |
| **Mahle** | Filters, engine parts | mahle-aftermarket.com |
| **Gates** | Belts, tensioners, water pumps | gates.com |
| **Monroe / Tenneco** | Shock absorbers | Monroe catalogue |
| **Ferodo / TRW** | Brake pads, discs | TRW catalogue |
| **Champion** | Filters, spark plugs | champion-autoparts.com |

**Most provide:**
- Downloadable Excel/CSV fitment guides (free)
- APIs for dealers/distributors (requires agreement)
- Cross-reference lookup tools on their websites

---

### TECDOC for Parts & Cross-References

When subscribed to TECDOC, you also get:
- Complete OEM cross-reference table (OEM number → aftermarket alternatives)
- Part descriptions in multiple languages
- Product images (standardised)
- Technical attributes (dimensions, specifications)
- Application data (fitment)

This eliminates the need to manually build cross-reference tables.

---

## Barcodes

### Standards Used in Motor Spares

| Standard | Format | Use case |
|----------|--------|----------|
| **EAN-13 / GTIN-13** | 13-digit numeric | Consumer product packaging barcodes |
| **EAN-8** | 8-digit numeric | Small packaging |
| **Code 128** | Variable length alphanumeric | Internal part labels, GRN labels, bin barcodes |
| **QR Code** | 2D matrix | Job cards, bin locations, customer vehicle tags |
| **ITF-14** | 14-digit numeric | Carton/case quantities |

### Assigning Barcodes
- EAN-13 comes from the manufacturer's packaging — scan and store in `barcode_ean`
- Internal Code 128 is generated by the system for `barcode_code128` — format: `PREFIX + PART_NUMBER + CHECK_DIGIT`
- GS1 manages the global EAN prefix assignments: [gs1.org](https://www.gs1.org)
- For self-assigned internal barcodes, use a company prefix (6-digit company + 6-digit sequence + 1 check)

### Barcode Scanning Hardware
- **USB HID barcode scanners**: plug into PC, scanned text appears in the focused input field. No driver needed.
- **Bluetooth scanners**: mobile warehouse use
- **2D scanners** (can read QR + barcodes): recommended — future-proof

---

## African Market Specifics

### Most Common Makes in Southern/Central Africa

Based on vehicle registration statistics, prioritise these makes for fitment data:

1. Toyota (dominant — 35–45% market share in many African markets)
2. Nissan / Datsun
3. Ford
4. Mazda
5. Volkswagen
6. Mitsubishi (especially Canter LCVs)
7. Isuzu (D-Max pickup + commercial trucks)
8. Hyundai / Kia
9. Mercedes-Benz (commercial + passenger)
10. Land Rover / Range Rover

**Chinese brands growing rapidly (2020+):**
- Haval / GWM
- Chery / Exeed
- BAIC
- FAW
- Foton
- BYD (EVs)
- Dongfeng

For Chinese brands, parts data is less standardised. Rely on local importer price lists.

### Local Distributor Contacts (Zimbabwe example)
| Make | Distributor |
|------|------------|
| Toyota | Toyota Zimbabwe / Willowvale |
| Nissan | Zimoco |
| Isuzu | Isuzu Zimbabwe |
| Mercedes | DaimlerChrysler Zimbabwe |
| Ford | Ford Motor Company Southern Africa |

Local distributors often provide parts catalogues and official part numbers for their market-specific variants (which may differ from global models).

---

## Data Import Tools / Packages

### Backend (Laravel)
```composer
maatwebsite/laravel-excel     # Excel/CSV import & export
barryvdh/laravel-dompdf       # PDF generation (for printed docs)
league/csv                    # Low-level CSV parsing
```

### Creating a Price List Importer
```php
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SupplierPriceListImport implements ToCollection, WithHeadingRow
{
    public function __construct(
        private readonly SupplierPriceList $priceList,
        private readonly array $columnMapping,  // User-configured during upload
    ) {}

    public function collection(Collection $rows): void
    {
        $rows->chunk(500)->each(function ($chunk) {
            $items = $chunk->map(function ($row) {
                $partNumber = $row[$this->columnMapping['supplier_part_number']];
                $part = Part::whereHas('crossReferences', fn($q) =>
                    $q->where('reference_number', $partNumber)
                )->first();

                return [
                    'price_list_id'        => $this->priceList->id,
                    'part_id'              => $part?->id,
                    'supplier_part_number' => $partNumber,
                    'cost_price'           => $row[$this->columnMapping['cost_price']],
                ];
            });

            SupplierPriceListItem::upsert($items->toArray(), ['price_list_id', 'supplier_part_number']);
        });
    }
}
```

---

## Summary: Recommended Data Strategy

| Phase | Action | Tool / Source |
|-------|--------|--------------|
| **Setup** | Seed vehicle makes/models | CarQuery API import command |
| **Setup** | Seed common engine codes | Manual entry from list in Module 8 |
| **Setup** | Import existing supplier price lists | Excel importer |
| **Ongoing** | Update supplier prices | Price list import (quarterly) |
| **Growth** | Integrate TECDOC | TECDOC REST API subscription |
| **Ongoing** | Add new vehicles manually | Admin UI |
| **Ongoing** | VIN decode on vehicle registration | NHTSA API (free) |
| **Ongoing** | Fitment corrections | Manual by stock controller |
