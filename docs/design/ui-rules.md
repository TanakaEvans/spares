# UI Rules & HCI Standards

> Binding rules for every screen. The bar: a counter assistant with basic computer skills serves a customer **without thinking about the software**. Smoothness is not polish — it is the product. Complements [design-system.md](design-system.md) (visual tokens) and [navigation-and-layout.md](navigation-and-layout.md) (shell).

---

## 1. Speed & Feedback (the smoothness budget)

| Rule | Budget |
|------|--------|
| Any click/keypress gives visible feedback | < 100 ms (button pressed state, row highlight) |
| Search-as-you-type results | < 300 ms after debounce |
| Page navigation (Inertia visit) | < 1 s; skeleton rows if slower — never a blank screen |
| Posting a document (invoice, GRN) | < 2 s; button shows spinner + "Posting…"; double-click is impossible (button disables on first click) |
| A complete cash sale, scan → receipt | < 90 s including payment |

- Filters/tabs use Inertia **partial reloads** (`only:[...]`) — never a full page refetch.
- Long jobs (imports, big reports) are queued: instant acknowledgement + notification when done. The UI never freezes on a spinner longer than 3 s.

## 2. Keyboard-First (operational screens)

POS, GRN, stock-take count, receipt allocation, journal entry — fully drivable without a mouse:

- Global in-module: `F2` part search · `F4` customer search · `F6` vehicle · `F9` pay/post · `Esc` cancel dialog · `Alt+N` primary "New" action.
- `Enter` = confirm line / advance to next field; `Tab` order strictly follows visual order; arrow keys move through result lists and table rows.
- **Barcode scanners are keyboards**: after every line-add, focus returns to the search input automatically so consecutive scans just work. Scanning into the "wrong" place never destroys data — search inputs are the default focus.
- Shortcuts are shown in the UI (grey hints on buttons: "Pay **F9**"), not hidden in a manual.

## 3. Minimal Interaction Cost

- **Click budget per frequent task**: cash sale ≤ 6 interactions; receive a 10-line GRN ≤ 15; open a job card ≤ 8. Measure against these when designing.
- Smart defaults everywhere: today's date, user's branch, Cash Customer, default price list, primary bin, last-used payment method. The user confirms far more than they enter.
- Never ask for what the system knows: selecting a part fills description/price/VAT; selecting a customer fills terms/price list/address; scanning a vehicle reg fills the whole vehicle block.
- Auto-advance: entering a GRN line quantity moves to the next line; counting a stock-take line loads the next automatically.
- Bulk beats repeat: multi-select with bulk actions on every list (assign roles, activate parts, allocate receipts oldest-first).

## 4. Error Prevention > Error Messages

- Illegal actions are **disabled with a reason tooltip**, not clickable-then-rejected ("Post" disabled: "2 lines have no bin location").
- Inline validation on blur, not only on submit; the submit error summary links each error to its field and scrolls to the first.
- Duplicate guards warn *before* creation (part number, EAN, supplier ref, customer name fuzzy-match).
- Destructive/irreversible actions: `ConfirmDialog` naming the object ("Post INV-20260922-0042 for $94.88? Posted invoices cannot be edited."), Cancel focused by default, red only for true destruction.
- Unsaved changes guard on every form (`useForm.isDirty`) — navigation asks; **drafts** save automatically on operational documents (a power cut mid-GRN loses nothing — see resilience doc).

## 5. Errors, When They Happen

- Message pattern: **what happened + why + what to do**, in plain language. ("Cannot complete sale — only 2 of 90915-YZZD3 in stock at this branch. Reduce quantity, or check Bulawayo branch: 6 available.")
- No codes-only errors, no "Something went wrong" without a retry path, no silent failures — every failed action produces visible feedback.
- Server validation errors always land back on the exact field.

## 6. Consistency Contracts (every screen, no exceptions)

- Component-level specs (buttons, modals, tables, forms, sidebar theme) are pinned in [component-standards.md](component-standards.md) — this section sets the behavioural contracts, that file sets the exact anatomy.
- The four archetypes (List/Form/Detail/Operational) are the only page shapes; primary action top-right; same filter row anatomy; same table anatomy; same tab behaviour.
- One `StatusBadge`, one `MoneyDisplay`, one date format (from settings), one empty-state pattern (icon + sentence + primary action), one toast position.
- Identical actions have identical names everywhere: *Save* (stays), *Save & New*, *Post* (irreversible ledger write), *Void*, *Cancel*. Never mix "Submit/OK/Apply/Done".
- Search boxes always search the same fields per entity (parts: number + OEM + cross-refs + description + barcode; customers: name + number + phone; documents: number + reference).

## 7. Recognition Over Recall

- Every code shows its meaning: `A-1-C-04 (Aisle A, Shelf C)`, `Net 30 (due 22 Oct)`, engine `1GD-FTV — 2.8L diesel turbo`.
- Recently used parts/customers surface first in searches; per-user favourites on POS.
- Comboboxes over free-text wherever a finite list exists; free text only for genuinely free content.
- Breadcrumbs + document numbers keep the user located; the browser Back button always works (Inertia history).

## 8. Touch & Environment Reality

- Counter/workshop screens assume: dusty fingers, gloves off/on, standing users, sunlight glare. Minimum 40 px targets on operational screens, high contrast (design-system tokens meet WCAG AA), `NumberPad` for all qty/money entry on touch.
- Workshop tablets: stock-take and job-card status flows are one-thumb operable.
- Printing is fire-and-forget: receipt prints while the next sale starts; print failure shows a retry toast, never blocks the till.

## 9. Undo & Safety Nets

- Prefer reversible flows: suspend/recall sales, draft documents, void-within-shift (audited) before the heavier credit-note path.
- Batch imports are undoable as a batch.
- Everything posted is *correctable by a counter-document* (credit note, reversal journal, supplier return) — the UI offers the correct counter-action on the document it corrects ("Create credit note" button on the invoice).

## 10. Progressive Disclosure

- The 80% path is visible; the 20% path is one click away ("More options", advanced filters collapsed, line-detail drawer). Never 40 fields on first render when 8 are typically used.
- Role-appropriate density: cashiers see the POS, not the module jungle (default landing page per role = a setting).

## 11. Language & Microcopy

- English, plain, regional: "bakkie-friendly" wording where natural (Reg no, VAT invoice, EFT). No developer vocabulary (no "record", "entity", "null", "invalid payload").
- Buttons say the outcome: "Post & Print Receipt", not "OK".
- Numbers: money always 2dp with thousands separators; quantities trim trailing zeros; negatives in red parentheses on financial screens.

## 12. Page Guides (built-in help on every page)

- **Every page carries a collapsible Page Guide** — full spec: [page-guide.md](page-guide.md). Collapsed by default, expands only on trigger (`ⓘ` button or `F1`), explains what happens on the page, links to related pages, and closes with `Esc`.
- It never auto-opens, never overlays a modal, never interrupts work — help is pulled, not pushed.
- Guide content ships with the sub-module (derived from its spec) and supports admin-edited local overrides. A page without a guide fails review; a behaviour change without a guide update fails review.

## 13. Smooth Linked Navigation (everything related is one click away)

The ERP is a web of related records — the UI must honour that web:

- **Every rendered entity reference is a hyperlink.** A customer name on an invoice → customer profile. A part number on a GRN line → part detail. A supplier on a price list, a technician on a job, a document number in a ledger entry, a batch reference in an import log — all links, everywhere, with no exceptions. If the screen can name it, the user can click it.
- Links are **Inertia `<Link>`s** — instant SPA transitions, no full reloads, browser Back always returns to the exact prior state (filters, page, tab, scroll preserved via `preserveState`/`preserveScroll`).
- **Never dead-end the user.** Every detail page links onward to its related records (invoice → its credit notes, its receipts, its customer, each part) and backward to where it came from (breadcrumb + contextual "back to …").
- **Ctrl/middle-click works**: links are real `<a href>` under the hood — open a part in a new tab while keeping the sale on screen.
- Hover affordance: linked entities show the link style on hover (subtle underline, pointer) — discoverable without being noisy in dense tables.
- Cross-module jumps carry context: "Create credit note" on an invoice opens the form pre-linked to that invoice; "Order this part" from a low-stock row opens a PO line pre-filled. The user never re-finds what they were just looking at.
- Related-record links respect permissions (plain text + lock hint when denied) — never a click that 403s.

## 14. Global Search & Quick Actions

- `Ctrl+K` opens the **command palette** from anywhere — spec: [global-search-and-quick-actions.md](global-search-and-quick-actions.md). Search parts, customers, suppliers, documents, vehicles and pages in one box; recent items first; actions ("New quote for…") inline.
- Recently viewed records (last 10 per entity) surface at the top of every entity search.

## 15. Enforcement

- These rules are review criteria: a PR that violates a numbered rule is returned, citing the rule (e.g. "UI-4: action rejected instead of disabled-with-reason", "UI-13: part number rendered as plain text").
- New shared patterns must be added HERE first, then used — no one-off inventions on a single screen.
