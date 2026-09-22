# Local Data Seeding Strategy (PRIMARY — no paid integrations)

> **This is the adopted strategy.** No TECDOC subscription, no per-user API costs, no runtime dependency on any external service. All vehicle and parts data lives in the local database, loaded from bundled seed packs, supplier price-list imports, and admin screens. The earlier [vehicle-parts-data-strategy.md](vehicle-parts-data-strategy.md) describes the optional paid path — keep it on file for the future, but **build this one**.

---

## Why full-local wins here

1. **Zero recurring cost** — nothing to pass on to users.
2. **Zero internet dependency** — the POS works during outages (a daily reality in the region).
3. **The regional car parc is narrow.** Southern Africa's vehicle population is dominated by a few dozen models. You do not need 100M records — you need the right ~500 models and the parts *this business* stocks, done accurately.

---

## The Seed Packs (bundled CSVs, committed to the repo)

All reference data ships as version-controlled CSV files, loaded by idempotent seeders:

```
database/data/
  vehicle_makes.csv          (~45 makes)
  vehicle_models.csv         (~450 models with year ranges & body types)
  vehicle_variants.csv       (~1,200 popular variants with engine codes)
  engine_codes.csv           (~250 common engines)
  part_categories.csv        (full category tree, 3 levels)
  part_brands.csv            (~120 aftermarket + OEM brands)
  units_of_measure.csv
  labour_codes.csv           (~150 standard workshop operations)
  chart_of_accounts.csv      (the full COA from Module 7)
  vat_rates.csv
  payment_terms.csv
  payment_methods.csv
  currencies.csv             (USD, ZWG, ZAR, BWP, ZMW, MZN, EUR, GBP, CNY, JPY)
```

```bash
php artisan db:seed --class=ReferenceDataSeeder   # loads/refreshes all packs
```

Seeders use `updateOrCreate` keyed on natural codes — safe to re-run after editing a CSV. **How the CSVs are produced:** once, by the developer, from free sources (NHTSA/CarQuery pulls, manufacturer public catalogues, distributor lists) — the *results* are committed; production never calls any API.

---

## The Regional Car Parc (what the vehicle packs must cover)

Priority list for South Africa, Zimbabwe, Zambia, Botswana, Mozambique, Malawi — ordered by what actually rolls into a spares shop:

### Tier 1 — must be complete to variant level (~80% of counter queries)
| Make | Models |
|------|--------|
| **Toyota** | Hilux (all gens: 2.4/2.8 GD-6, 2.5/3.0 D-4D, 2.7 VVTi, older 2.4/2.8 diesel), Corolla + Quest, Fortuner, Land Cruiser 70/76/79/105/200/300 & Prado, HiAce/Quantum (incl. Sesfikile), Avanza, Etios, Yaris, RAV4, Corolla Cross, Vitz (grey import), Raum, Wish, Belta |
| **Volkswagen** | Polo + Polo Vivo (the SA best-seller), Golf 4–8, Tiguan, T-Cross, Amarok, Caddy, Transporter/Crafter, Jetta |
| **Nissan** | NP200, NP300 Hardbody, Navara, Almera, Micra, X-Trail, Qashqai, Patrol, Sunny/Sentra (older), Caravan, Tiida, Note (grey import) |
| **Ford** | Ranger (all gens: 2.2/3.2 Duratorq, 2.0 Bi-Turbo), EcoSport, Figo, Fiesta, Focus, Everest, Bantam (legacy), Transit |
| **Isuzu** | KB series, D-Max, MU-X, N-series trucks (NPR/NQR) |
| **Hyundai** | i10/Grand i10, i20, Accent, Creta, Tucson, H-100 bakkie, H-1, Venue |
| **Kia** | Picanto, Rio, Sportage, Sorento, K2700 bakkie, Seltos |
| **Mercedes-Benz** | C-Class (W203/204/205), E-Class, Sprinter, Vito, Actros/Atego trucks |
| **BMW** | 3 Series (E46/E90/F30/G20), 5 Series, X3, X5, 1 Series |
| **Honda** | Fit/Jazz, Civic, CR-V, Ballade, Brio, HR-V |
| **Mazda** | Mazda2, Mazda3, CX-5, BT-50, Familia/323 (legacy, still everywhere), Demio (grey import) |
| **Mitsubishi** | Triton, Pajero/Pajero Sport, ASX, Colt (legacy), Canter trucks |

### Tier 2 — model level with main variants
Suzuki (Swift, Ertiga, Jimny, Baleno — fast-growing in SA), Renault (Kwid, Sandero, Duster, Clio), Peugeot (208, 2008, Boxer), Opel (Corsa, Astra), Chevrolet (Spark, Cruze, Utility — legacy but common), Land Rover (Defender, Discovery, Range Rover), Jeep, Subaru, Audi (A3/A4/Q5), Datsun (Go), Fiat, Tata (Indica, Super Ace, trucks), Mahindra (Pik Up, Scorpio, Bolero, XUV)

### Tier 3 — growing Chinese brands (model level, expand as requested)
Haval/GWM (H2, H6, Jolion, P-Series, Steed), Chery (Tiggo 4/7/8, QQ legacy), BAIC, JAC, Foton, FAW, Dongfeng, BYD, Omoda/Jaecoo, Jetour

### Grey imports (critical for Zimbabwe/Zambia/Malawi)
Japanese-domestic-market vehicles arrive by the shipload: Toyota Vitz/Passo/Raum/Wish/Fun Cargo/Probox, Honda Fit, Nissan March/Note/AD Van, Mazda Demio, Mitsubishi Colt. **These JDM variants differ from SA-spec models** (engines like Toyota 1NZ-FE, 2NZ-FE, 1KR-FE) — the packs include them as distinct variants with their JDM engine codes.

### Engine codes pack — highest-value examples
`1GD-FTV, 2GD-FTV, 1KD-FTV, 2KD-FTV, 5L-E, 2L-T, 1NZ-FE, 2NZ-FE, 1KR-FE, 2TR-FE, 1GR-FE, 1HZ, 1HD-FTE, 4Y` (Toyota); `YD25DDTi, K9K, HR15DE, QG16DE, KA24E, TD27, ZD30` (Nissan); `2.2/3.2 Duratorq P4AT/P5AT, 2.0 EcoBlue` (Ford); `CAXA, CBZB, CJZA, BTS 1.6, EA888 gen1-3, 2.0 TDI CFF` (VW); `4JJ1, 4JK1, 4JA1, 4JH1, 4HK1` (Isuzu); `4D56, 4M40, 4N15` (Mitsubishi); `G4LA, G4FC, D4CB` (Hyundai/Kia); `WLAT/WEAT 2.5/3.0 MZR-CD` (Mazda/Ford legacy)

---

## Parts Data — the four local inflows

```
① Supplier price-list imports  ──►  THE main SKU source. Every distributor
   (Excel/CSV wizard, 6.2)          (Midas, AutoZone SA, Goldwagen, Masterparts,
                                    local NGK/Bosch/GUD agents…) emails price
                                    lists already. Import → map → activate.

② Brand fitment guides         ──►  GUD/Fram (filters), NGK (plugs), Gates
   (free downloads, imported         (belts), Ferodo (brakes) publish free
   via the same wizard)              fitment catalogues for the SA market —
                                    these ARE the local TECDOC, at $0.

③ Opening data migration       ──►  The business's existing records
   (operations/data-migration.md)   (Excel stock sheets, card systems).

④ Manual entry + POS "request  ──►  Daily gap-filling; every new part follows
   new part"                        workflows/new-part-onboarding.md.
```

The cross-reference table grows the same way: brand guides map OEM ↔ aftermarket numbers (e.g. Toyota 90915-YZZD3 ↔ GUD Z88 ↔ Fram PH4998), and staff add pairs as they learn them — an entered cross-reference is institutional knowledge captured forever.

### Import wizard requirements (build once, reuse everywhere)
- Accepts `.xlsx/.csv`; user maps columns visually; template downloads provided
- Preview + validation report BEFORE commit (duplicates, bad numbers, missing categories)
- Idempotent re-import (natural-key upsert); full log of every import batch with undo of the batch
- Used by: parts, cross-references, fitments, supplier price lists, customers, suppliers, opening stock, opening balances

---

## Optional free extras (nice-to-have, degrade gracefully)

| Feature | Source | Cost | Offline behaviour |
|---------|--------|------|-------------------|
| VIN decode on vehicle registration | NHTSA vPIC API | Free | Field simply stays manual |
| New-model seed refresh | Dev regenerates CSVs yearly | Free | N/A (repo update) |

No other network calls. `ExternalCatalogSource` stays bound to `NullCatalogSource`.

---

## What this means for the schema
Nothing changes — the same tables (`vehicle_makes/models/variants`, `parts`, `part_fitments`, `part_cross_references`) serve both strategies. Only the *inflow* differs. `part_fitments.source` values are `manual`, `supplier`, `brand_guide`, `migration` (no `tecdoc` rows).
