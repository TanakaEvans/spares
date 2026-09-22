# Global Search & Quick Actions (Command Palette)

> One box, `Ctrl+K`, from anywhere: find any part, customer, supplier, document, vehicle or page — and act on it. The fastest possible path between "I need X" and "I'm looking at X". Complements [ui-rules.md](ui-rules.md) §14.

---

## The palette

```
┌──────────────────────────────────────────────────────────────┐
│ 🔍 hilux oil filter_                                    Esc  │
├──────────────────────────────────────────────────────────────┤
│ RECENT                                                       │
│   📦 90915-YZZD3 · Oil Filter Toyota OEM        14 in stock  │
│   👥 Mupfumi Motors · trade · $1,240 owing                   │
│ PARTS                                                        │
│ ▸ 📦 90915-YZZD3 · Oil Filter — Toyota OEM      14 in stock  │
│   📦 Z88 · GUD Oil Filter                        6 in stock  │
│ PAGES                                                        │
│   ⓘ Fitment Lookup — find parts for a vehicle               │
│ ACTIONS                                                      │
│   ⚡ New quote with "hilux oil filter"…                      │
├──────────────────────────────────────────────────────────────┤
│ ↑↓ navigate · Enter open · Ctrl+Enter new tab · Tab actions  │
└──────────────────────────────────────────────────────────────┘
```

### Behaviour
1. `Ctrl+K` (and the top-bar search box) opens it over any page; `Esc` closes; the page beneath is untouched.
2. **Grouped results**, ranked: Recent (last 10 viewed per entity, local) → exact code matches → Parts → Customers → Documents → Suppliers → Vehicles → Pages → Actions. Max 4 per group with "show all in {module}" tail links that open the module's list pre-filtered.
3. Searches the same fields the entity lists search ([ui-rules.md](ui-rules.md) §6): parts by number/OEM/cross-ref/barcode/description; customers by name/number/phone; documents by number/customer ref; vehicles by registration/VIN.
4. **Prefix operators** for precision: `p:` parts only · `c:` customers · `d:` documents · `v:` vehicles · `>` actions/pages only. A scanned barcode in the palette jumps straight to the part.
5. Results show the one status fact that matters (stock for parts, balance/hold for customers, status for documents) so many lookups end *at the palette* without navigating.
6. Result rows honour permissions (unqueryable entities never appear); Enter opens, `Ctrl+Enter` opens in a new tab (real links per UI-13).

## Quick actions

Actions appear when the query matches an action verb or an entity is highlighted:

| Context | Actions offered |
|---------|----------------|
| Part highlighted | View · Add to current POS sale (if POS open) · New PO line · Print label |
| Customer highlighted | View · New sale · New quote · Record payment · Statement |
| Vehicle highlighted | View history · New job card · Fitment lookup |
| Bare verbs (`new invoice`, `receive`, `stock take`) | Jump to the create screen |

Actions are the same permission-gated routes as the menus — the palette is a shortcut layer, never a second capability system.

## Implementation notes

- Backend: one `GlobalSearchController` fanning out per-entity scoped queries (indexed columns from [database.md](../coding-standards/database.md)); 300 ms debounce; ≤ 15 rows per response; branch-scoped like everything else.
- Frontend: shared `<CommandPalette>` mounted in both layouts; recent items in `localStorage` (ids only — details re-fetched, so stale data never shows).
- Ships in Phase 0 as part of the shared UI kit skeleton (entity sources register as their modules land) — see [tasks/00-foundations.md](../tasks/00-foundations.md).
