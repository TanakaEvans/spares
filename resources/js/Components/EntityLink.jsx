import { Link } from '@inertiajs/react';

/**
 * The ONE way to render a reference to another record (UI-13: every entity
 * reference is a hyperlink). Give it a `type` and an `id` (or a `number` for
 * entities that only have a list page) and it resolves the right route.
 *
 *   <EntityLink type="part" id={l.part_id} label={l.part_number} mono />
 *   <EntityLink type="customer" id={inv.customer_id} label={inv.customer} />
 *   <EntityLink type="grn" number={grn.grn_number} />   // → GRN list, filtered
 *
 * Falls back to plain text when there is nothing to link to, so it is always
 * safe to use in a table cell.
 */

// type → [route name, how to build params]. 'id' routes need `id`; 'search'
// routes land on a list page pre-filtered by the number/label.
const ROUTES = {
    part: ['inventory.parts.show', 'id'],
    customer: ['customers.show', 'id'],
    supplier: ['suppliers.show', 'id'],
    invoice: ['sales.invoices.show', 'id'],
    credit_note: ['sales.credit-notes.show', 'id'],
    quote: ['sales.quotes.show', 'id'],
    order: ['sales.orders.show', 'id'],
    purchase_order: ['purchasing.orders.show', 'id'],
    job: ['workshop.jobs.show', 'id'],
    vehicle: ['workshop.vehicles.show', 'id'],
    journal: ['finance.journals.show', 'id'],
    model: ['vehicle-ref.models.show', 'id'],
    price_list: ['sales.price-lists.show', 'id'],
    stock_take: ['inventory.stock-takes.show', 'id'],
    branch: ['admin.branches.show', 'id'],
    user: ['auth.users.show', 'id'],
    grn: ['purchasing.grns.show', 'id'],
    supplier_invoice: ['purchasing.invoices.show', 'id'],
    supplier_return: ['purchasing.returns.show', 'id'],
    supplier_credit: ['purchasing.supplier-credits.show', 'id'],
    import_shipment: ['purchasing.imports.show', 'id'],
    receipt: ['finance.receipts.show', 'id'],
    payment: ['finance.payments.show', 'id'],
    technician: ['workshop.technicians.show', 'id'],
    warranty_claim: ['workshop.warranty.show', 'id'],
    bin: ['inventory.bins.show', 'id'],
    adjustment: ['inventory.adjustments.show', 'id'],
    transfer: ['inventory.transfers.show', 'id'],
    delivery_note: ['sales.delivery-notes.show', 'id'],
    // detail page when an id is known, else the list pre-filtered by number/label
    make: ['vehicle-ref.makes.show', 'id', ['vehicle-ref.makes.index', 'search']],
    engine: ['vehicle-ref.engines.show', 'id', ['vehicle-ref.engines.index', 'search']],
    // list pages, pre-filtered
    stock: ['inventory.stock.index', 'search'],
    category: ['inventory.parts.index', 'category_id'],
    brand: ['inventory.brands.index', 'search'],
    gl_account: ['finance.gl.index', 'account'],
    layby: ['sales.laybys.show', 'id'],
};

export function entityHref(type, { id, number, label, params } = {}) {
    const def = ROUTES[type];
    if (!def || typeof route !== 'function' || !route().has(def[0])) return null;
    const [name, mode, fallback] = def;
    if (mode === 'id') {
        // Detail routes with a list fallback take only their own id — list-filter params don't apply.
        if (id) return route(name, { ...(fallback ? {} : (params ?? {})), [routeParam(name)]: id });
        if (!fallback) return null;
        const value = number ?? label;
        return value ? route(fallback[0], { [fallback[1]]: value }) : null;
    }
    const value = number ?? label;
    if (mode === 'category_id') return id ? route(name, { ...(params ?? {}), category_id: id }) : null;
    return value ? route(name, { ...(params ?? {}), [mode]: value }) : null;
}

// Ziggy needs the parameter name used in the route definition.
function routeParam(name) {
    return {
        'inventory.parts.show': 'part', 'customers.show': 'customer', 'suppliers.show': 'supplier',
        'sales.invoices.show': 'invoice', 'sales.credit-notes.show': 'creditNote', 'sales.quotes.show': 'quote',
        'sales.orders.show': 'order', 'purchasing.orders.show': 'order', 'workshop.jobs.show': 'job',
        'workshop.vehicles.show': 'vehicle', 'finance.journals.show': 'journal', 'sales.price-lists.show': 'priceList',
        'inventory.stock-takes.show': 'stockTake', 'admin.branches.show': 'branch', 'auth.users.show': 'user',
        'purchasing.grns.show': 'grn', 'purchasing.invoices.show': 'supplierInvoice', 'purchasing.returns.show': 'supplierReturn',
        'purchasing.imports.show': 'shipment', 'finance.receipts.show': 'receipt', 'finance.payments.show': 'payment',
        'workshop.technicians.show': 'technician', 'workshop.warranty.show': 'claim', 'inventory.bins.show': 'bin',
        'inventory.adjustments.show': 'adjustment', 'inventory.transfers.show': 'transfer',
        'sales.laybys.show': 'layby', 'sales.delivery-notes.show': 'deliveryNote',
        'vehicle-ref.models.show': 'model', 'vehicle-ref.makes.show': 'make', 'vehicle-ref.engines.show': 'engine',
    }[name] ?? 'id';
}

export default function EntityLink({ type, id, number, label, params, mono = false, muted = false, className = '', title, children }) {
    const text = children ?? label ?? number;
    const href = entityHref(type, { id, number, label, params });
    const base = `${mono ? 'font-mono ' : ''}${muted ? 'text-slate-500' : 'text-slate-800'} ${className}`;

    if (!href || text == null || text === '') {
        return <span className={base}>{text ?? '—'}</span>;
    }

    return (
        <Link
            href={href}
            title={title}
            onClick={(e) => e.stopPropagation()}
            className={`${base} hover:text-orange-600 hover:underline underline-offset-2 decoration-orange-300 transition-colors`}
        >
            {text}
        </Link>
    );
}
