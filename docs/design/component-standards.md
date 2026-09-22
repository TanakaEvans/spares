# Component Standards (buttons, modals, theme — the systematic rules)

> Exact, non-negotiable specs for every recurring UI element. [design-system.md](design-system.md) defines the tokens; this file defines **how each component uses them**. If two screens render the same element differently, one of them is a bug.

---

## 1. Theme — one source of truth

- All colours come from the token set in [design-system.md](design-system.md), exposed as Tailwind theme extensions (`@theme` in `resources/css/app.css`). **Raw hex values in JSX/Blade are forbidden** — reviewers grep for `#[0-9A-Fa-f]{6}` in components.
- Semantic status colours map through `<StatusBadge>`'s single map — no ad-hoc `bg-green-…` for statuses outside it.
- Dark sidebar + light content is the app's identity; no per-module colour schemes, no per-screen accent changes.

### 1a. Sidebar theme — follows the main layout, everywhere

**Rule: there is exactly ONE sidebar theme — the main layout's (`bg-slate-900` dark navy, as in the existing `AdminLayout`).** Every module sidebar inherits it automatically because:

- Sidebar chrome (colours, spacing, active/hover states, section labels, collapse behaviour, the bottom Home/Settings/Logout block) is implemented **once** in the shared `ModuleLayout` sidebar component.
- Module `nav.js` configs supply **content only** (sections, labels, routes, icons, badges) — they cannot pass classes, colours, or style props. The config schema has no styling fields, by design.
- Exact sidebar spec: `w-64 bg-slate-900`; section label `text-[11px] uppercase tracking-wider text-slate-400 px-4 pt-5 pb-2`; item `text-slate-300 hover:bg-slate-800 hover:text-white rounded-md mx-2 px-3 py-2 text-sm flex items-center gap-3`; active item `bg-slate-800 text-white border-l-2 border-[--accent]`; icon `size-4 shrink-0`; badge counts `ml-auto` pill using StatusBadge tokens.
- The existing `FinanceSidebar.jsx` is **deprecated**: its content migrates to a `nav.js` config rendered by the shared component ([tasks/00-foundations.md](../tasks/00-foundations.md) 0.6 covers the shared sidebar; the migration is a Phase 4 finance task).

## 2. Buttons

| Variant | Use | Style | Per page |
|---------|-----|-------|----------|
| `primary` | THE main action (Save, Post, Pay) | Accent bg, white text | **Max one visible per view/dialog** |
| `secondary` | Alternative actions (Save & New, Print) | White bg, border, text colour |  |
| `ghost` | Low-emphasis (Cancel, row actions) | No bg/border until hover |  |
| `destructive` | True destruction (Void, Delete draft) | Red bg — never for Post/Save | Only inside a ConfirmDialog flow |
| `link` | Inline navigation styled as text | Accent text underline-on-hover |  |

Rules:
1. Sizes: `sm` (tables/toolbars), `md` (default), `lg` (POS/touch, min 40px). Never mix sizes in one button row.
2. Labels are verbs naming the outcome ("Post & Print Receipt") — see [ui-rules.md](ui-rules.md) §11. Icon-only buttons require a tooltip + `aria-label`.
3. Placement: primary right-most in footers and top-right in page headers; Cancel/ghost to its left. Same order everywhere.
4. Busy state: on submit, the clicked button disables, shows spinner + progressive label ("Posting…"); sibling buttons disable too. Built into the shared `<Button>` via `processing` prop — never hand-rolled.
5. Disabled buttons always carry a `title` explaining why (UI-4).

## 3. Modals & dialogs (Radix `Dialog` only)

| Type | Size | Use | Dismissable by overlay-click/Esc? |
|------|------|-----|-----------------------------------|
| `confirm` | sm (28rem) | Irreversible actions — via shared `<ConfirmDialog>` | Esc = Cancel; overlay-click = Cancel |
| `form` | md (36rem) / lg (48rem) | Quick create/edit without leaving context (add contact, add line) | Esc asks if dirty; overlay-click disabled when dirty |
| `picker` | md | Entity selection with search (advanced part picker, allocation) | Yes |
| `tender` | lg | POS payment | Esc = back to cart; never loses cart |

Rules:
1. **Max one modal open** — no stacking, ever. A flow needing a second layer is redesigned (inline drawer/step).
2. Title = the action ("Void invoice INV-…?"), body = consequence, footer = ghost Cancel (focused for destructive) + one primary.
3. Anything longer than a form modal (multi-section, tabs) is a **page**, not a modal.
4. Focus is trapped (Radix default); on close, focus returns to the trigger; first field auto-focused in form modals.
5. Full-page complex creation never happens in a modal — Form archetype pages exist for that.

## 4. Toasts & banners

- Toasts (flash results): top-right, max 3 stacked, success auto-dismiss 5s, errors persist with ✕; always `role="alert"`; text pattern "Posted INV-… — $94.88", optional single action link ("View").
- Banners (persistent state): full-width strip under the page header for ongoing conditions (offline queue, missing exchange rate, stock-take freeze, scope-authorisation needed). Colour by semantic token; never auto-dismiss; always say the resolving action.
- Never use a toast for something requiring action (that's a banner or dialog); never a banner for a one-off result.

## 5. Tables (`<DataTable>` contract)

- Column order convention: identity (number/name, linked per UI-13) → descriptive → status (`StatusBadge`) → numerics right-aligned `tabular-nums` → dates → row-actions `⋯` menu (ghost, right-most).
- Row click opens the record (whole row is a link target); row-actions menu holds everything else — max 6 items, destructive last and separated.
- Header sort indicators, sticky header on long tables, 25/page footer pagination showing "1–25 of 312".
- Empty/filtered-empty states per [ui-rules.md](ui-rules.md) §6; loading = skeleton rows matching column layout.

## 6. Forms

- Every control wrapped in `<FormField>` (label, required `*`, error slot) — bare inputs fail review.
- Grid: `grid-cols-2 gap-4` desktop, single column < md; full-width for textareas/tables; related fields grouped in cards with `text-lg` section headings.
- Selects: Radix Select for ≤ ~15 static options; searchable combobox (`PartSearch`-family) for entities; never a 200-option native select.
- Dates via one shared date input honouring the configured format; money via `MoneyInput` (right-aligned, 2dp on blur); percentages suffixed `%`.
- Footer: sticky on long forms — ghost Cancel + primary Save (+ optional secondary Save & New). Dirty-guard per UI-4.

## 7. Icons, badges, empty states

- Icons: Lucide only, `size-4` inline / `size-5` nav / `size-8` empty-state; always accompanied by text except in the row-actions trigger and top-bar (tooltipped).
- `StatusBadge`: rounded-full, `text-xs font-medium px-2 py-0.5` — the only way a status renders.
- Count badges (sidebar, tabs): `ml-auto`, muted bg, switch to accent when > 0 requires attention.

## 8. Component change protocol

Shared components live in `resources/js/Components/ui/` + the kit list in [design-system.md](design-system.md). Changing one = changing every screen: PR must state the visual impact, and additions to this file land **in the same PR** as the component. No screen-local forks of shared components — extend via props/variants (CVA) or don't.
