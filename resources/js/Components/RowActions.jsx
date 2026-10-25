import { Link } from '@inertiajs/react';
import * as DropdownMenu from '@radix-ui/react-dropdown-menu';
import { MoreHorizontal } from 'lucide-react';

/**
 * The row-actions `⋯` menu (component-standards §5): right-most column, ghost
 * trigger, max 6 items, destructive last and separated. Clicks never leak to
 * the row, so a clickable row still opens the record.
 *
 *   <RowActions actions={[
 *     { label: 'Edit', icon: Pencil, onSelect: () => editor.open(row, values) },
 *     { label: 'Open', icon: Eye, href: route('x.show', row.id) },
 *     { label: 'Delete', icon: Trash2, destructive: true, onSelect: () => confirmDelete({...}) },
 *   ]} />
 *
 * An action with `disabled: 'reason'` renders greyed with the reason as its tooltip.
 */
export default function RowActions({ actions, label = 'Row actions' }) {
    const list = actions.filter(Boolean);
    if (list.length === 0) return null;
    const normal = list.filter((a) => !a.destructive);
    const destructive = list.filter((a) => a.destructive);
    const stop = (e) => e.stopPropagation();

    const item = (a) => {
        const Icon = a.icon;
        const cls = `flex items-center gap-2.5 rounded-md px-2.5 py-1.5 text-sm outline-none ${
            a.disabled ? 'cursor-not-allowed text-slate-300' : a.destructive
                ? 'text-red-600 data-[highlighted]:bg-red-50'
                : 'text-slate-700 data-[highlighted]:bg-slate-100'
        }`;
        const body = <>{Icon && <Icon className="w-4 h-4" />}<span className="flex-1">{a.label}</span></>;
        return (
            <DropdownMenu.Item
                key={a.label}
                disabled={!!a.disabled}
                title={typeof a.disabled === 'string' ? a.disabled : undefined}
                asChild={!!a.href && !a.disabled}
                onSelect={a.onSelect}
                className={cls}
            >
                {a.href && !a.disabled ? <Link href={a.href} className={cls}>{body}</Link> : body}
            </DropdownMenu.Item>
        );
    };

    return (
        <span onClick={stop} onKeyDown={stop} className="inline-flex">
            <DropdownMenu.Root modal={false}>
                <DropdownMenu.Trigger
                    aria-label={label}
                    className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-300"
                >
                    <MoreHorizontal className="w-4 h-4" />
                </DropdownMenu.Trigger>
                <DropdownMenu.Portal>
                    <DropdownMenu.Content
                        align="end"
                        sideOffset={4}
                        onClick={stop}
                        className="z-50 min-w-[11rem] rounded-xl border border-slate-200 bg-white p-1 shadow-lg"
                    >
                        {normal.map(item)}
                        {destructive.length > 0 && normal.length > 0 && <DropdownMenu.Separator className="my-1 h-px bg-slate-100" />}
                        {destructive.map(item)}
                    </DropdownMenu.Content>
                </DropdownMenu.Portal>
            </DropdownMenu.Root>
        </span>
    );
}
