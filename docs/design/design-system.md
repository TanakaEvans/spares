# Design System

> Visual language for SparesPro. Built on Tailwind CSS v4 + the existing shadcn/ui-style components (Radix + CVA) in `resources/js/Components/ui/`.

---

## Brand & Colour Tokens

Industrial, trustworthy, high-contrast — a parts counter is a bright, busy place and screens are read at arm's length.

| Token | Value | Usage |
|-------|-------|-------|
| `--primary` | `#0F1B35` (deep navy) | Sidebar, headers, primary text on light |
| `--primary-light` | `#1A2B50` | Sidebar hover/active states |
| `--accent` | `#E8590C` (workshop orange) | Primary buttons, active nav, focus rings, key totals |
| `--accent-hover` | `#C94D0A` | Button hover |
| `--bg` | `#F5F7FA` | App background |
| `--surface` | `#FFFFFF` | Cards, tables, panels |
| `--border` | `#E2E8F2` | Dividers, input borders |
| `--text` | `#1A2744` | Body text |
| `--text-muted` | `#6B7A99` | Secondary text, labels |

### Semantic colours (status — never reuse the accent for status)

| Meaning | Tailwind classes | Used for |
|---------|-----------------|----------|
| Success / posted / paid / in stock | `bg-green-100 text-green-800` | Badges, toasts |
| Info / in progress | `bg-blue-100 text-blue-800` | |
| Warning / low stock / awaiting | `bg-yellow-100 text-yellow-800` | |
| Danger / overdue / out of stock / rejected | `bg-red-100 text-red-800` | |
| Neutral / draft | `bg-gray-100 text-gray-700` | |
| Special order / warranty | `bg-purple-100 text-purple-800` | |

All status rendering goes through one `<StatusBadge status={...} />` component — the status→colour map lives in exactly one file.

---

## Typography

| Role | Spec |
|------|------|
| App font | System stack (default Tailwind) — fast, no font loading at the POS |
| Page title | `text-2xl font-semibold` |
| Section heading | `text-lg font-medium` |
| Body | `text-sm` (15px effective) — ERP screens are dense |
| Table text | `text-sm`, numeric columns `tabular-nums text-right` |
| Money | Always right-aligned, `tabular-nums`, 2 decimals, thousands separators |
| Part numbers / codes | `font-mono text-sm` — monospace makes 90915-YZZD3 scannable |

---

## Spacing & Density

ERP screens favour density over whitespace, but consistently:

- Page padding: `p-6` desktop, `p-4` mobile
- Card padding: `p-4`; card grid gap: `gap-4`
- Table rows: `py-2.5` (compact but tappable)
- Form field vertical rhythm: `space-y-4`; two-column form grids `grid-cols-2 gap-4` collapsing to one column below `md`
- Section separation: `space-y-6`

---

## Core Components (existing + to build)

| Component | Status | Notes |
|-----------|--------|-------|
| Button, Input, Badge, Card, Dialog, Select, Tabs, Table | ✅ exist in `Components/ui/` | Radix + CVA |
| `StatusBadge` | 🆕 | Single status→colour map |
| `FormField` | 🆕 | Label + control + error wrapper (see react-inertia.md) |
| `DataTable` | 🆕 | Sortable headers, pagination footer, empty state, row actions menu |
| `SearchInput` | 🆕 | Debounced, with clear button and scan-friendly focus behaviour |
| `MoneyDisplay` | 🆕 | Currency symbol + formatted value; negative in red parentheses |
| `PartSearch` | 🆕 | The most important component: combobox searching part_number / OEM / description / barcode, shows stock + price in results |
| `CustomerSearch` | 🆕 | Combobox with balance + hold badge in results |
| `VehiclePicker` | 🆕 | Make → model → variant cascading selects, or registration lookup |
| `ConfirmDialog` | 🆕 | For all destructive/posting actions ("Post invoice INV-…? This cannot be edited.") |
| `NumberPad` | 🆕 | Touch qty/price entry for POS |
| `EmptyState` | 🆕 | Icon + message + primary action for empty tables |

---

## Interaction Rules

1. **Posting is explicit and confirmed.** Anything that writes stock or GL (post invoice, post GRN, post stock take) uses `ConfirmDialog` naming the document number and stating irreversibility.
2. **Destructive = red, and never the default focus.** Cancel is focused by default in destructive dialogs.
3. **Keyboard-first at the counter.** POS and GRN screens fully operable without a mouse: `F2` search part, `F4` customer, `F9` payment, `Enter` adds line, barcode scanners type-and-enter into whichever input has focus — search inputs auto-refocus after each add.
4. **Flash messages** (Inertia shared props) render as toasts top-right, auto-dismiss 5s, `role="alert"`; errors persist until dismissed.
5. **Loading states**: buttons show spinner + disable during `processing`; tables show skeleton rows on filter changes, never a blank flash.
6. **Every list screen** has: search, at least one filter, column sort, pagination (25/page), an empty state with a create action, and an Export button where the doc specifies one.
7. **Print views** are separate Blade/PDF templates — never "print the web page".

---

## Responsiveness

| Breakpoint | Behaviour |
|-----------|-----------|
| Desktop (≥1280) | Sidebar fixed open, tables full width |
| Laptop (≥1024) | Same; 4-col dashboards drop to 3 |
| Tablet (≥768) | Sidebar collapses to icons/overlay; POS optimised for touch here (workshop tablets) |
| Phone | Read-mostly: dashboards, lookups, approvals. Data-entry screens (GRN, stock take) are tablet-minimum; phone shows a "best on a larger screen" notice but does not block |

---

## Accessibility

- Labels on every control (`FormField` enforces it); visible focus rings (accent colour) throughout.
- Colour is never the only signal — badges carry text, not just colour.
- All Radix primitives keep their built-in keyboard/ARIA behaviour; don't rebuild them.
- Minimum touch target 40px on POS/tablet screens.
