# Module 8: Vehicle & Parts Reference

> The master reference database for vehicles and parts compatibility. Answers: "What vehicles does this part fit?" and "What parts fit my car?" This module is the backbone of accurate parts selling.

---

## Sub-modules

| # | Sub-module | Description |
|---|-----------|-------------|
| 8.1 | [Vehicle Makes](#81-vehicle-makes) | All car brands |
| 8.2 | [Vehicle Models & Variants](#82-vehicle-models--variants) | Model/year/engine variants |
| 8.3 | [Parts Cross-Reference](#83-parts-cross-reference) | OEM ↔ Aftermarket ↔ Competitor numbers |
| 8.4 | [Fitment Guide](#84-fitment-guide) | Part → vehicle compatibility |
| 8.5 | [Engine Codes](#85-engine-codes) | Engine code reference |
| 8.6 | [Supersession Management](#86-supersession-management) | Part number changes |
| 8.7 | [Technical Bulletins](#87-technical-bulletins) | Fitment warnings and notes |

---

## 8.1 Vehicle Makes

### Database Tables

#### `vehicle_makes`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | varchar(100) | "Toyota", "Ford", "BMW" |
| code | varchar(10) | "TOY", "FOR", "BMW" |
| country_of_origin | varchar(100) | Japan, Germany... |
| is_active | boolean | |
| logo_path | varchar(255) | Brand logo |
| sort_order | int | Display order (popular makes first) |

### Suggested Initial Make List (prioritised for Southern/Central Africa)
Popular makes to seed first (in order of market prevalence):

```
Rank  Make           Origin      Notes
1     Toyota         Japan       Most common in Africa
2     Nissan         Japan       Includes Datsun
3     Ford           USA/Germany Including Ranger, Focus, Fiesta
4     Volkswagen     Germany     Golf, Polo, Passat
5     Mazda          Japan       
6     Honda          Japan       
7     Hyundai        Korea       
8     Kia            Korea       
9     Mitsubishi     Japan       Including Canter
10    Isuzu          Japan       D-Max, commercial
11    Mercedes-Benz  Germany     
12    BMW            Germany     
13    Chevrolet      USA         
14    Opel           Germany     
15    Peugeot        France      
16    Renault        France      
17    Jeep           USA         
18    Land Rover     UK          Including Defender
19    Subaru         Japan       
20    Suzuki         Japan       
21    Chery          China       Growing market
22    Haval/GWM      China       Growing in Africa
23    FAW            China       Commercial/trucks
24    Foton          China       LCVs and trucks
25    BYD            China       EVs, growing
26    Tata           India       Commercial
27    Mahindra       India       
28    BAIC           China       
29    Dongfeng        China       
```

> Additional makes can be added via the admin interface or bulk import.

---

## 8.2 Vehicle Models & Variants

### Database Tables

#### `vehicle_models`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| make_id | bigint FK | |
| name | varchar(100) | "Hilux", "Corolla", "Ranger" |
| body_type | varchar(30) | `sedan`, `hatchback`, `suv`, `pickup`, `van`, `bus`, `truck` |
| segment | varchar(30) | `A-segment` through `F-segment`, `LCV`, `HCV` |
| year_from | smallint | First year this model sold |
| year_to | smallint | Last year (null = still in production) |
| generation | varchar(30) | e.g. "Mk8 (2020–)" |
| is_active | boolean | |

#### `vehicle_variants`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| model_id | bigint FK | |
| name | varchar(150) | Full variant name e.g. "2.8 GD-6 4×4 AT" |
| engine_code | varchar(30) | e.g. "1GD-FTV" |
| engine_size_cc | int | e.g. 2755 |
| fuel_type | varchar(20) | `petrol`, `diesel`, `hybrid`, `electric`, `lpg` |
| power_kw | int | |
| power_hp | int | |
| cylinders | int | |
| aspiration | varchar(20) | `naturally_aspirated`, `turbo`, `supercharged` |
| transmission | varchar(20) | `manual`, `automatic`, `cvt`, `amt` |
| drive | varchar(10) | `4x2`, `4x4`, `awd`, `fwd`, `rwd` |
| year_from | smallint | |
| year_to | smallint | |
| market_region | varchar(50) | `global`, `southern_africa`, `europe`, etc. |

### Example: Toyota Hilux Variants
```
Toyota Hilux (model)
  ├── 2.4 GD-6 Raider 4×4 MT (2016–2020)     engine: 2GD-FTV, diesel, 110kW
  ├── 2.4 GD-6 Raider 4×4 AT (2016–2020)     engine: 2GD-FTV, diesel, 110kW
  ├── 2.8 GD-6 Legend 4×4 AT (2016–2020)     engine: 1GD-FTV, diesel, 150kW
  ├── 2.8 GD-6 Legend 4×4 AT (2020–current)  engine: 1GD-FTV, diesel, 165kW
  └── 2.4 GD-6 SRX 4×2 MT (2016–current)     engine: 2GD-FTV, diesel, 110kW
```

---

## 8.3 Parts Cross-Reference

### Purpose
A single part (e.g., an oil filter) may have dozens of cross-reference numbers:
- The OEM number (what Toyota prints on their box)
- The aftermarket manufacturer's number (what Bosch/Mann/WIX uses)
- Competitor's numbers (what another spares shop codes it as)

Cross-referencing enables staff to find the right part regardless of which number the customer arrives with.

### Cross-Reference Types

| Type | Description | Example |
|------|-------------|---------|
| `oem` | Original manufacturer number | Toyota: 90915-YZZD3 |
| `aftermarket` | Aftermarket brand equivalent | Mann: HU 7028 z |
| `superseded_by` | This number is replaced by another | Old → New |
| `competitor` | A competitor's catalogue code | GSF: 123456 |
| `ean` | International barcode | 4025533041001 |

See also `part_cross_references` table in [Module 1](01-inventory-management.md).

---

## 8.4 Fitment Guide

### How Fitment Data Flows

```
Vehicle: Toyota Hilux 2.8 GD-6 (2020)
               │
               ▼
     Fitment Guide query
               │
               ├── Oil Filters: Mann HU7028z, NGK OP555
               ├── Air Filters: Ryco A1787, Mann C25114
               ├── Timing Belt Kit: Gates K025475XS
               ├── Glow Plugs: NGK Y-550J (set of 4)
               └── Brake Pads Front: Ferodo FDB4473
```

### At Point of Sale — Vehicle Registration Lookup
1. Cashier scans/types vehicle registration
2. System looks up registered vehicle in `customer_vehicles`
3. If found: auto-filter parts catalogue to compatible parts
4. If not found: customer selects make/model/year manually
5. All search results show fitment confidence: ✓ Confirmed, ~ Likely, ? Verify

### See `part_fitments` table in [Module 1](01-inventory-management.md).

---

## 8.5 Engine Codes

### `engine_codes`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| code | varchar(30) | "1GD-FTV" |
| description | varchar(255) | "Toyota 2.8L diesel, direct injection, turbo" |
| make_id | bigint FK | Which manufacturer |
| capacity_cc | int | |
| fuel_type | varchar(20) | |
| aspiration | varchar(20) | |
| cylinders | int | |
| valves_per_cylinder | int | |
| notes | text | |

### Common Engine Codes Reference (seeded data)
```
Toyota:
  1GD-FTV  — 2.8L diesel (Hilux, Fortuner, Land Cruiser Prado 2015+)
  2GD-FTV  — 2.4L diesel (Hilux, Fortuner, HiAce 2015+)
  1GR-FE   — 4.0L V6 petrol (Land Cruiser Prado, 4Runner)
  2TR-FE   — 2.7L petrol (HiLux, Fortuner pre-2016)
  1KZ-TE   — 3.0L diesel (older Land Cruiser Prado)
  5A-FE    — 1.5L petrol (older Corolla)
  4A-FE    — 1.6L petrol (older Corolla)

Volkswagen:
  EA888    — 1.4/1.8/2.0 TSI petrol (Golf, Polo)
  EA189    — 2.0 TDI diesel (affected by emissions scandal)
  CAYB     — 1.6 TDI diesel (Polo, Caddy)

Ford:
  Duratorq — 2.2/3.2 TDCi diesel (Ranger, Transit)
  EcoBoost — 1.0/1.5/2.0 petrol (Focus, Fiesta)

Isuzu:
  4JJ1     — 2.5L diesel (D-Max)
  4JK1     — 2.5L diesel (MU-X)
```

---

## 8.6 Supersession Management

When a manufacturer discontinues a part number and replaces it with a new one, the system must:
1. Retain the old part record (for history)
2. Auto-redirect searches for the old number to the new number
3. Display a "supersession" notice to staff

See `part_supersessions` table in [Module 1](01-inventory-management.md).

### Chain Supersessions
`A → B → C` — the system follows the chain automatically to find the current active part.

---

## 8.7 Technical Bulletins

### Purpose
Service notes about specific fitment issues, recalls, technical anomalies. E.g.:
- "Bosch injectors for Hilux 2.8 2016: use part W800 not W750 — W750 was recalled Jan 2023"
- "Toyota Fortuner brake pads: aftermarket EBC pads may cause ABS sensor interference"

### `technical_bulletins`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| bulletin_number | varchar(20) | TB-YYYY-XXX |
| title | varchar(255) | |
| description | text | |
| severity | varchar(20) | `info`, `warning`, `critical` |
| affected_makes | json | Array of make_ids |
| affected_models | json | Array of model_ids |
| affected_parts | json | Array of part_ids |
| issue_date | date | |
| resolution | text | What to do |
| source | varchar(100) | Who issued the bulletin |
| is_active | boolean | |

---

## Data Sources

See [data-sources.md](../data-sources.md) for full details on where to get vehicle and parts data.

**Quick reference:**

| Source | What it gives | Cost | URL |
|--------|--------------|------|-----|
| TECDOC / TecDoc | Full parts catalogue + fitment + cross-refs | Subscription | tecdoc.net |
| CarQuery API | Makes + models + specs | Free (limited) | carqueryapi.com |
| NHTSA API | US vehicle makes/models/VIN decode | Free | vpic.nhtsa.dot.gov/api |
| OEM sites | Official part numbers | Free | Per manufacturer |
| AutoCare ACES | Industry standard fitment XML | Subscription | autocare.org |

---

## Pages to Build

| Page | Route | Component |
|------|-------|-----------|
| Vehicle makes | `vehicle-ref.makes.index` | `VehicleRef/Makes/Index.jsx` |
| Vehicle models | `vehicle-ref.models.index` | `VehicleRef/Models/Index.jsx` |
| Model variants | `vehicle-ref.variants.index` | `VehicleRef/Variants/Index.jsx` |
| Fitment lookup | `vehicle-ref.fitment` | `VehicleRef/Fitment/Index.jsx` |
| Cross-reference search | `vehicle-ref.cross-ref` | `VehicleRef/CrossRef/Index.jsx` |
| Technical bulletins | `vehicle-ref.bulletins.index` | `VehicleRef/Bulletins/Index.jsx` |
| Engine codes | `vehicle-ref.engines.index` | `VehicleRef/Engines/Index.jsx` |

---

## Integration Points

| Module | Relationship |
|--------|-------------|
| **Inventory** | Fitment data links parts to vehicles in catalogue |
| **Sales** | Vehicle registration lookup at POS filters parts |
| **Workshop** | Vehicle model determines flat-rate times for labour codes |
| **Customers** | Customer's registered vehicles feed the fitment filter |
| **Purchasing** | Fitment data confirms correct part ordered for customer |
