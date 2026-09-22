# Coding Standards: React / Inertia.js

> Standards for all frontend code. React 18, Inertia.js 2, Tailwind CSS v4, shadcn/ui component style.

---

## File & Component Conventions

### File Naming
| Type | Convention | Location |
|------|-----------|----------|
| Pages | `PascalCase.jsx` | `Modules/{Module}/resources/js/Pages/` |
| Components | `PascalCase.jsx` | `Modules/{Module}/resources/js/Components/` |
| Shared Components | `PascalCase.jsx` | `resources/js/Components/` |
| Hooks | `useCamelCase.js` | `resources/js/hooks/` |
| Utilities | `camelCase.js` | `resources/js/lib/` |
| Layouts | `PascalCase.jsx` | `resources/js/Layouts/` |

### Page File Structure
```
Modules/InventoryManagement/resources/js/Pages/
  Parts/
    Index.jsx      ← List view
    Create.jsx     ← New record form
    Edit.jsx       ← Edit existing record
    Show.jsx       ← Detail/read-only view
  StockTakes/
    Index.jsx
    Create.jsx
    Count.jsx      ← Custom action page
  Stock/
    Index.jsx
    Adjustments/
      Index.jsx
      Create.jsx
```

---

## Component Patterns

### Functional Components Only — No Class Components

```jsx
// ✅ Good
export default function PartForm({ part, categories, brands, onSubmit }) {
  // ...
}

// ❌ Never
class PartForm extends React.Component { ... }
```

### Props Destructuring at Top

```jsx
export default function PartCard({ part, showStock = true, onEdit }) {
  const { part_number, description, brand, stock } = part;
  // ...
}
```

### Keep Components Focused
A component does one thing. If it's doing multiple things, split it.

- Under ~100 lines: single-concern component ✅
- 100–200 lines: consider splitting ⚠️
- Over 200 lines: must split ❌

---

## Inertia.js Patterns

### `useForm` for All Forms

```jsx
import { useForm } from '@inertiajs/react';

export default function CreatePart({ categories, brands }) {
  const { data, setData, post, processing, errors, reset } = useForm({
    part_number: '',
    description: '',
    category_id: '',
    brand_id: '',
    is_oem: false,
    is_active: true,
  });

  function submit(e) {
    e.preventDefault();
    post(route('inventory.parts.store'), {
      onSuccess: () => reset(),
    });
  }

  return (
    <form onSubmit={submit}>
      <Input
        value={data.part_number}
        onChange={(e) => setData('part_number', e.target.value)}
        error={errors.part_number}
      />
      {/* ... */}
      <Button type="submit" disabled={processing}>Save Part</Button>
    </form>
  );
}
```

### `usePage` for Shared Data

```jsx
import { usePage } from '@inertiajs/react';

export default function Layout({ children }) {
  const { auth, flash } = usePage().props;

  return (
    <div>
      {flash.success && <Alert type="success">{flash.success}</Alert>}
      <nav>Welcome, {auth.user.name}</nav>
      {children}
    </div>
  );
}
```

### Programmatic Navigation

```jsx
import { router } from '@inertiajs/react';

// Navigate to a page
router.visit(route('inventory.parts.index'));

// With method
router.delete(route('inventory.parts.destroy', part.id), {
  onSuccess: () => { /* ... */ },
});

// Partial reload — refresh only specific props
router.reload({ only: ['parts', 'filters'] });
```

### `Link` for Anchor Tags

```jsx
import { Link } from '@inertiajs/react';

<Link href={route('inventory.parts.show', part.id)}>
  {part.part_number}
</Link>
```

---

## Page Structure Pattern

Every page follows the same structure:

```jsx
import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';

// 1. Named export of the layout assignment
CreatePart.layout = (page) => <AdminLayout children={page} title="Create Part" />;

// 2. Default export of the page component
export default function CreatePart({ categories, brands }) {
  return (
    <>
      <Head title="Create Part" />

      <div className="space-y-6">
        {/* Page header */}
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-semibold text-gray-900">Create Part</h1>
        </div>

        {/* Page content */}
        <PartForm categories={categories} brands={brands} />
      </div>
    </>
  );
}
```

---

## Table (Index) Pages

Standard pattern for list/table pages:

```jsx
export default function PartsIndex({ parts, filters, categories }) {
  const [search, setSearch] = useState(filters.search ?? '');

  function applyFilter(key, value) {
    router.get(route('inventory.parts.index'), 
      { ...filters, [key]: value },
      { preserveState: true, replace: true }
    );
  }

  return (
    <div className="space-y-4">
      {/* Filters row */}
      <div className="flex gap-3">
        <SearchInput
          value={search}
          onChange={setSearch}
          onSearch={(v) => applyFilter('search', v)}
          placeholder="Search part number, description..."
        />
        <Select
          value={filters.category_id}
          options={categories}
          onChange={(v) => applyFilter('category_id', v)}
          placeholder="All categories"
        />
      </div>

      {/* Table */}
      <DataTable
        data={parts.data}
        columns={columns}
        pagination={parts}
      />
    </div>
  );
}
```

---

## Shared UI Components (shadcn/ui style)

All UI primitives live in `resources/js/Components/ui/`. These are already partially set up in the project (Button, Input, Badge, Dialog, etc.).

### Component Usage

```jsx
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Card, CardHeader, CardContent } from '@/Components/ui/card';
import { Dialog, DialogTrigger, DialogContent } from '@/Components/ui/dialog';
```

### Custom Component: `FormField`

Create a wrapper to eliminate repetitive label + input + error patterns:

```jsx
// resources/js/Components/FormField.jsx
export function FormField({ label, error, required, children }) {
  return (
    <div className="space-y-1">
      {label && (
        <label className="text-sm font-medium text-gray-700">
          {label}{required && <span className="text-red-500 ml-1">*</span>}
        </label>
      )}
      {children}
      {error && <p className="text-sm text-red-600">{error}</p>}
    </div>
  );
}

// Usage
<FormField label="Part Number" required error={errors.part_number}>
  <Input
    value={data.part_number}
    onChange={(e) => setData('part_number', e.target.value)}
  />
</FormField>
```

---

## Tailwind CSS Conventions

### Class Ordering
Follow the official Tailwind ordering (use `prettier-plugin-tailwindcss`):
1. Layout (display, position, flex/grid)
2. Sizing (w, h, min/max)
3. Spacing (p, m, gap)
4. Typography (text, font)
5. Visual (bg, border, shadow, rounded)
6. Effects (opacity, transition)
7. Responsive prefixes last

### Do Not Use Inline Styles
```jsx
// ✅ Tailwind
<div className="bg-orange-100 text-orange-800 px-2 py-1 rounded-full text-xs font-medium">

// ❌ Inline style
<div style={{ backgroundColor: '#ffedd5', color: '#9a3412' }}>
```

### Status Badge Pattern
Use consistent semantic colours for status badges throughout the system:

| Status | Class |
|--------|-------|
| Active / Complete / Paid | `bg-green-100 text-green-800` |
| Draft / Pending | `bg-gray-100 text-gray-700` |
| In Progress | `bg-blue-100 text-blue-800` |
| Warning / Review | `bg-yellow-100 text-yellow-800` |
| Error / Overdue / Rejected | `bg-red-100 text-red-800` |
| Cancelled | `bg-red-50 text-red-500 line-through` |
| Special Order | `bg-purple-100 text-purple-800` |

Create a `<StatusBadge status={job.status} />` component that maps statuses to classes so the mapping is maintained in one place.

---

## Custom Hooks

### `useDebounce` — for search inputs

```jsx
// resources/js/hooks/useDebounce.js
export function useDebounce(value, delay = 300) {
  const [debouncedValue, setDebouncedValue] = useState(value);

  useEffect(() => {
    const timer = setTimeout(() => setDebouncedValue(value), delay);
    return () => clearTimeout(timer);
  }, [value, delay]);

  return debouncedValue;
}
```

### `useConfirm` — for destructive actions

```jsx
// resources/js/hooks/useConfirm.js
export function useConfirm() {
  const [state, setState] = useState({ open: false, resolve: null, message: '' });

  function confirm(message) {
    return new Promise((resolve) => {
      setState({ open: true, message, resolve });
    });
  }

  function handleResponse(confirmed) {
    state.resolve(confirmed);
    setState({ open: false, resolve: null, message: '' });
  }

  return { confirm, state, handleResponse };
}
```

---

## Ziggy Routes

Use `route()` helper (from Ziggy) for all URL generation. Never hardcode URLs.

```jsx
// ✅ Named route
href={route('inventory.parts.show', { part: part.id })}

// ✅ In router.visit
router.visit(route('inventory.parts.edit', part.id));

// ❌ Hardcoded
href={`/inventory/parts/${part.id}`}
```

---

## State Management Principles

1. **No global state library** (no Redux, no Zustand). Inertia shared props (`usePage`) cover auth, flash, and global config.
2. **Form state**: always `useForm` from Inertia.
3. **UI state** (modal open, selected tab, filter values): `useState` locally in the component.
4. **Server-driven state**: Inertia re-renders automatically after `router.visit` or form submit.
5. **Don't sync server state into local state** — read directly from Inertia props.

---

## Performance

- Use `router.reload({ only: [...] })` for partial refreshes instead of full page reloads.
- Paginate all list views (25 items default). Never load all records.
- Lazy-load heavy components (e.g., chart library) with `React.lazy` + `Suspense`.
- Use `useMemo` for expensive computations, not for simple derived values.
- `useCallback` only when passing callbacks to heavily memoized children.

---

## Accessibility

- Every `<input>` has an associated `<label>` (via `htmlFor` / `id` or wrapper).
- Interactive elements are keyboard-accessible (all Radix primitives handle this).
- Destructive actions require confirmation (`useConfirm` dialog).
- Flash messages use `role="alert"`.
- Tables have `<caption>` or `aria-label`.
