// Finance & Accounts nav — content only (component-standards §1a).
// Shape + rules: docs/design/navigation-and-layout.md §2.
// Ordered by daily frequency: money in, money out, bank, then the ledger and period-end.
import {
    Banknote, BookOpen, Columns2, FileSpreadsheet, HandCoins, HeartPulse, Landmark, ListTree,
    Lock, PenLine, Percent, Play, Receipt, Rows3, TrendingUp, Truck, Users,
} from 'lucide-react';

export default {
    moduleKey: 'finance',
    moduleLabel: 'Finance & Accounts',
    moduleIcon: Banknote,

    sections: [
        {
            section: 'Receivables',
            items: [
                {
                    label: 'Customer Receipts', route: 'finance.receipts.index', icon: HandCoins,
                    children: [{ label: 'New Receipt', route: 'finance.receipts.create' }],
                    related: [
                        { label: 'Customers', route: 'customers.index', icon: Users },
                        { label: 'Tax Invoices', route: 'sales.invoices.index', icon: Receipt },
                    ],
                },
            ],
        },
        {
            section: 'Payables',
            items: [
                {
                    label: 'Supplier Payments', route: 'finance.payments.index', icon: Rows3,
                    children: [{ label: 'New Payment', route: 'finance.payments.create' }],
                    related: [
                        { label: 'Supplier Invoices', route: 'purchasing.invoices.index', icon: FileSpreadsheet },
                        { label: 'Suppliers', route: 'suppliers.index', icon: Truck },
                    ],
                },
                {
                    label: 'Payment Run', route: 'finance.payment-run.index', icon: Play,
                    related: [{ label: 'Supplier Invoices', route: 'purchasing.invoices.index', icon: FileSpreadsheet }],
                },
            ],
        },
        {
            section: 'Banking',
            items: [
                { label: 'Bank Accounts', route: 'finance.bank.index', icon: Landmark },
            ],
        },
        {
            section: 'General Ledger',
            items: [
                {
                    label: 'Journals', route: 'finance.journals.index', icon: PenLine,
                    children: [{ label: 'New Journal', route: 'finance.journals.create' }],
                },
                { label: 'GL Enquiry', route: 'finance.gl.index', icon: BookOpen },
                { label: 'Chart of Accounts', route: 'finance.coa.index', icon: ListTree },
                {
                    label: 'Periods', route: 'finance.periods.index', icon: Lock,
                    related: [{ label: 'System Health', route: 'admin.health', icon: HeartPulse }],
                },
            ],
        },
        {
            section: 'Tax',
            items: [
                { label: 'VAT Returns', route: 'finance.vat.index', icon: Percent },
            ],
        },
        {
            section: 'Statements',
            items: [
                { label: 'Trial Balance', route: 'finance.reports.trial-balance', icon: Columns2 },
                { label: 'Income Statement', route: 'finance.reports.income-statement', icon: TrendingUp },
                { label: 'Balance Sheet', route: 'finance.reports.balance-sheet', icon: Landmark },
            ],
        },
    ],
};
