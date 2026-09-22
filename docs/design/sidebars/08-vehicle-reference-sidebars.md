# Sub-module Sidebars: Vehicle & Parts Reference

Sub-module sidebar layouts for [Module 8: Vehicle & Parts Reference](../../modules/08-vehicle-parts-reference.md), following the universal template and hard rules in [README.md](README.md).

## 8.1 Vehicle Makes

> Context: entered via Vehicle Reference › Vehicle Makes. [Spec](../../modules/08-vehicle-parts-reference/8.1-vehicle-makes.md)

```
↰ Vehicle Reference
▍ VEHICLE MAKES  (badge-check)
──────────────────────────────
WORK
  Makes List .............. vehicle-ref.makes.index         (list)
  Add Make ................ vehicle-ref.makes.create        (plus-circle)
  Bulk Import ............. vehicle-ref.makes.import        (upload)
QUICK LINKS ⇄
  Models & Variants ....... vehicle-ref.models.index        (car)
  Engine Codes ............ vehicle-ref.engines.index       (cog)
  Fitment Lookup .......... vehicle-ref.fitment             (search)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Add Make / Bulk Import render only for Super Admin and Data Administrator; all other roles see the Makes List alone (view-only).
- Sort-order drag-reorder and the duplicate-merge tool live inside the Makes List screen, not as sidebar items.

## 8.2 Vehicle Models & Variants

> Context: entered via Vehicle Reference › Models & Variants. [Spec](../../modules/08-vehicle-parts-reference/8.2-vehicle-models-variants.md)

```
↰ Vehicle Reference
▍ MODELS & VARIANTS  (car)
──────────────────────────────
WORK
  Models List ............. vehicle-ref.models.index        (list)
  New Model ............... vehicle-ref.models.create       (plus-circle)
  Variants List ........... vehicle-ref.variants.index      (rows-3)
  New Variant ............. vehicle-ref.variants.create     (plus-circle)
QUICK LINKS ⇄
  Vehicle Makes ........... vehicle-ref.makes.index         (badge-check)
  Fitment Lookup .......... vehicle-ref.fitment             (search)
  Engine Codes ............ vehicle-ref.engines.index       (cog)
  Technical Bulletins ..... vehicle-ref.bulletins.index     (file-warning)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Create items are gated to Super Admin / Data Administrator; other roles get the two list screens read-only.
- Variants with an `engine_code` missing from the Engine Codes table surface on the Variants List data-quality widget — a screen concern, not a sidebar badge (no live count defined in the spec).

## 8.3 Parts Cross-Reference

> Context: entered via Vehicle Reference › Parts Cross-Reference. [Spec](../../modules/08-vehicle-parts-reference/8.3-parts-cross-reference.md)

```
↰ Vehicle Reference
▍ CROSS-REFERENCE  (shuffle)
──────────────────────────────
WORK
  Number Search ........... vehicle-ref.cross-ref           (search)
  Bulk Import ............. vehicle-ref.cross-ref.import    (upload)
QUICK LINKS ⇄
  Supersessions ........... vehicle-ref.supersessions.index (git-branch)
  Fitment Lookup .......... vehicle-ref.fitment             (search)
  Technical Bulletins ..... vehicle-ref.bulletins.index     (file-warning)
  Inventory: Parts ........ inventory.parts.index           (package-search)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Bulk Import is visible to Super Admin / Data Administrator only; counter, sales, and warehouse roles get Number Search alone.
- Per-part cross-reference maintenance happens on the part detail tab in Module 1 (reachable via the Inventory: Parts quick link), never from this sidebar.

## 8.4 Fitment Guide

> Context: entered via Vehicle Reference › Fitment Guide. [Spec](../../modules/08-vehicle-parts-reference/8.4-fitment-guide.md)

```
↰ Vehicle Reference
▍ FITMENT GUIDE  (search)
──────────────────────────────
WORK
  Fitment Lookup .......... vehicle-ref.fitment             (search)
INSIGHTS
  Fitment Review Queue .... vehicle-ref.fitment.review      (alert-circle)  [badge: flagged]
QUICK LINKS ⇄
  Models & Variants ....... vehicle-ref.models.index        (car)
  Cross-Reference ......... vehicle-ref.cross-ref           (shuffle)
  Supersessions ........... vehicle-ref.supersessions.index (git-branch)
  Technical Bulletins ..... vehicle-ref.bulletins.index     (file-warning)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Badge source: count of fitment records flagged / dropped to `verify` awaiting data-admin action (wrong-fitment returns and conflicting imports feed this queue automatically).
- Review Queue renders only for Data Administrator, Sales Manager, and Super Admin; lookup-only roles see WORK alone.
- Per-part fitment maintenance lives on the part detail tab in Module 1, not here.

## 8.5 Engine Codes

> Context: entered via Vehicle Reference › Engine Codes. [Spec](../../modules/08-vehicle-parts-reference/8.5-engine-codes.md)

```
↰ Vehicle Reference
▍ ENGINE CODES  (cog)
──────────────────────────────
WORK
  Engine Codes List ....... vehicle-ref.engines.index       (list)
  Add Engine Code ......... vehicle-ref.engines.create      (plus-circle)
QUICK LINKS ⇄
  Models & Variants ....... vehicle-ref.models.index        (car)
  Fitment Lookup .......... vehicle-ref.fitment             (search)
  Vehicle Makes ........... vehicle-ref.makes.index         (badge-check)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Add Engine Code is gated to Super Admin / Data Administrator; search-only roles see the list alone.
- Code detail (`vehicle-ref.engines.show`) opens from a list row and offers the "Find parts" pivot into the Fitment Guide — it is not a standing sidebar item.

## 8.6 Supersession Management

> Context: entered via Vehicle Reference › Supersessions. [Spec](../../modules/08-vehicle-parts-reference/8.6-supersession-management.md)

```
↰ Vehicle Reference
▍ SUPERSESSIONS  (git-branch)
──────────────────────────────
WORK
  Supersessions List ...... vehicle-ref.supersessions.index  (list)
  Record Supersession ..... vehicle-ref.supersessions.create (plus-circle)
QUICK LINKS ⇄
  Cross-Reference ......... vehicle-ref.cross-ref            (shuffle)
  Fitment Lookup .......... vehicle-ref.fitment              (search)
  Technical Bulletins ..... vehicle-ref.bulletins.index      (file-warning)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Record Supersession is available to Super Admin, Data Administrator, and Purchasing Officer (who learn of changes from supplier notices).
- Chain viewer (`vehicle-ref.supersessions.show`) opens from a list row's "Chain" link with per-hop stock — row-level, not a sidebar item.

## 8.7 Technical Bulletins

> Context: entered via Vehicle Reference › Technical Bulletins. [Spec](../../modules/08-vehicle-parts-reference/8.7-technical-bulletins.md)

```
↰ Vehicle Reference
▍ TECHNICAL BULLETINS  (file-warning)
──────────────────────────────
WORK
  Bulletins List .......... vehicle-ref.bulletins.index     (list)
  New Bulletin ............ vehicle-ref.bulletins.create    (plus-circle)
QUICK LINKS ⇄
  Fitment Lookup .......... vehicle-ref.fitment             (search)
  Supersessions ........... vehicle-ref.supersessions.index (git-branch)
  Cross-Reference ......... vehicle-ref.cross-ref           (shuffle)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- New Bulletin renders for Super Admin, Data Administrator, Sales Manager, and Workshop Foreman (drafts only); critical bulletins need Sales Manager approval before going active.
- Active critical bulletins pin to the top of the list screen; POS acknowledgement interrupts happen in Module 2, not from this sidebar.
