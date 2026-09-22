# Page Guide System (in-app contextual help)

> **Every page in SparesPro carries a collapsible Page Guide** — a built-in explanation of what happens on that page, who uses it, and where it leads. Collapsed by default, expands only when triggered, and hyperlinks to related pages and guides. This turns the ERP into its own training manual: a new cashier learns the POS *on* the POS.

---

## What it looks like

Collapsed (the default — costs almost no space):

```
┌──────────────────────────────────────────────────────────────┐
│ Goods Receiving (GRN)                    [ⓘ Page guide]  ▸  │  ← header row
├──────────────────────────────────────────────────────────────┤
│ … normal page content …                                      │
```

Expanded (after clicking the trigger or pressing `F1`):

```
┌──────────────────────────────────────────────────────────────┐
│ ⓘ About this page                                       ✕   │
│                                                              │
│ Here you receive stock that arrives from a supplier against  │
│ a Purchase Order. Count what actually arrived, reject        │
│ damaged items, assign bin locations, then Post — posting     │
│ increases stock on hand and cannot be undone.                │
│                                                              │
│ BEFORE YOU'RE HERE            AFTER YOU'RE DONE              │
│ → [Purchase Orders]           → [Supplier Invoices] (match)  │
│ → [Suppliers]                 → [Stock Levels] (see result)  │
│                                                              │
│ WATCH OUT                                                    │
│ • Over-receiving beyond 10% needs a supervisor               │
│ • Rejected items never enter stock — they go to              │
│   [Returns to Supplier]                                      │
│                                                              │
│ Shortcuts: F2 scan/search · Enter next line · F9 post        │
│ Full guide: [Goods Received Notes documentation]             │
└──────────────────────────────────────────────────────────────┘
```

---

## Behaviour rules

1. **Collapsed by default, always.** It never auto-expands, never overlays content on load, never nags. Triggers: the `ⓘ Page guide` header button, or `F1` anywhere on the page. `Esc` or `✕` closes.
2. **Remembers nothing per-page except by choice**: state is not persisted — every page load starts collapsed (guides are for the moment you need them, not permanent furniture). Exception: a per-user setting `ui.page_guide_prominent` (for trainees) renders the trigger with a label instead of just the icon.
3. **Expands in place** — pushes content down smoothly (150 ms ease), no modal, no route change; the user's form state is untouched.
4. **Every link inside a guide is a real in-app link** (Inertia `<Link>` / `route()`) — to other pages, to the relevant [module docs](../modules/), or to a sibling guide. Links respect permissions: a link the user can't access renders as plain text with a lock hint.
5. **Content is role-aware**: sections can be tagged by permission — a cashier's POS guide omits the supervisor-override paragraph a manager sees.
6. Present on **all** pages, including dialogs' parent pages; the four archetypes each get a tailored template (below). A page without a guide fails review.

---

## The `<PageGuide>` component

```jsx
// Shared component, used by every page via the layout
<PageGuide guideKey="purchasing.grns.create" />
```

- Lives in the page header row of `ModuleLayout`; `guideKey` defaults to the current route name — pages only pass it to share a guide.
- `F1` handler is registered globally in `ModuleLayout`.
- Renders markdown content with a restricted link resolver (`guide:` → another guide, `route:` → app page, `doc:` → docs viewer).

## Content storage & authoring

| Layer | Where | Editable by |
|-------|-------|-------------|
| Shipped guides | `Modules/{X}/resources/guides/{route.name}.md` — versioned with the code, written from each sub-module spec's Purpose/Workflow/Business Rules | Developers (PR) |
| Local overrides & additions | `page_guides` table (key, markdown, updated_by) — wins over the shipped file when present | Admins via Configuration Centre → Page Guides |

The override layer lets the business add its own house rules ("We never give cash refunds after 17:00") without a deployment. Shipped guide content is part of a sub-module's definition of done ([tasks files](../tasks/README.md)).

### Guide template per archetype

| Archetype | Sections |
|-----------|----------|
| List | What's listed here · How to find things (search fields!) · What the statuses mean · Where records come from / go next (links) |
| Form | What you're creating · Required vs optional fields worth explaining · What happens on save (links) |
| Detail | What this record is · What each tab shows · Available actions & when to use them (links) |
| Operational | The job in one paragraph · Step-by-step · Watch out (edge cases from the spec) · **Keyboard shortcuts** · Links before/after |

Each guide ends with a `Full guide:` link to its sub-module doc rendered in the in-app docs viewer (read-only markdown renderer for `docs/**` — ships in Phase 0 so guides can deep-link specs).

---

## Why this matters (and the discipline)

- Cuts training time drastically; support questions become "press F1".
- The guides are derived from the specs — when a sub-module doc changes behaviour, its guide changes in the same PR (**AGENTS.md rule: docs must never lie — guides are docs**).
- See [ui-rules.md](ui-rules.md) §12 (Page Guides) for the enforcement rule.
