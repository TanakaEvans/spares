// Finance & Accounts nav — content only (styling lives in ModuleLayout).
import {
    Banknote, BookOpen, ListTree, Lock, HandCoins, Rows3, Columns2,
    PenLine, LayoutList, Plus, Landmark, Receipt, FileText, Percent,
    Play, Calendar, TrendingUp,
} from 'lucide-react';

const navConfig = {
    moduleKey: 'finance',
    moduleLabel: 'Finance & Accounts',
    moduleIcon: Banknote,

    module: [
        {
            section: 'General Ledger',
            items: [
                { label: 'Chart of Accounts', route: 'finance.coa.index', icon: ListTree },
                { label: 'GL Enquiry', route: 'finance.gl.index', icon: BookOpen },
                { label: 'Journals', route: 'finance.journals.index', icon: PenLine },
                { label: 'Periods', route: 'finance.periods.index', icon: Lock },
            ],
        },
        {
            section: 'Receivables',
            items: [
                { label: 'Customer Receipts', route: 'finance.receipts.index', icon: HandCoins },
            ],
        },
        {
            section: 'Payables',
            items: [
                { label: 'Supplier Payments', route: 'finance.payments.index', icon: Rows3 },
                { label: 'Payment Run', route: 'finance.payment-run.index', icon: Play },
            ],
        },
        {
            section: 'Tax',
            items: [
                { label: 'VAT Returns', route: 'finance.vat.index', icon: Percent },
            ],
        },
        {
            section: 'Reports',
            items: [
                { label: 'Trial Balance', route: 'finance.reports.trial-balance', icon: Columns2 },
                { label: 'Income Statement', route: 'finance.reports.income-statement', icon: TrendingUp },
                { label: 'Balance Sheet', route: 'finance.reports.balance-sheet', icon: Landmark },
            ],
        },
    ],

    subModules: {
        coa: {
            label: 'Chart of Accounts', icon: ListTree, routePrefix: 'finance.coa',
            work: [{ label: 'All Accounts', route: 'finance.coa.index', icon: LayoutList }],
            quickLinks: [
                { label: 'GL Enquiry', route: 'finance.gl.index', icon: BookOpen },
                { label: 'Periods', route: 'finance.periods.index', icon: Lock },
            ],
        },
        periods: {
            label: 'Periods', icon: Lock, routePrefix: 'finance.periods',
            work: [{ label: 'Period Control', route: 'finance.periods.index', icon: Calendar }],
            quickLinks: [
                { label: 'Journals', route: 'finance.journals.index', icon: PenLine },
                { label: 'Trial Balance', route: 'finance.reports.trial-balance', icon: Columns2 },
            ],
        },
        gl: {
            label: 'GL Enquiry', icon: BookOpen, routePrefix: 'finance.gl',
            work: [{ label: 'Account Enquiry', route: 'finance.gl.index', icon: BookOpen }],
            quickLinks: [
                { label: 'Journals', route: 'finance.journals.index', icon: PenLine },
                { label: 'Chart of Accounts', route: 'finance.coa.index', icon: ListTree },
            ],
        },
        journals: {
            label: 'Journals', icon: PenLine, routePrefix: 'finance.journals',
            work: [
                { label: 'Journal Register', route: 'finance.journals.index', icon: LayoutList },
                { label: 'New Journal', route: 'finance.journals.create', icon: Plus },
            ],
            quickLinks: [
                { label: 'GL Enquiry', route: 'finance.gl.index', icon: BookOpen },
                { label: 'Chart of Accounts', route: 'finance.coa.index', icon: ListTree },
            ],
        },
        receipts: {
            label: 'Customer Receipts', icon: HandCoins, routePrefix: 'finance.receipts',
            work: [
                { label: 'Receipts & Ageing', route: 'finance.receipts.index', icon: LayoutList },
                { label: 'New Receipt', route: 'finance.receipts.create', icon: Plus },
            ],
            quickLinks: [
                { label: 'Customers', route: 'customers.index', icon: HandCoins },
                { label: 'Tax Invoices', route: 'sales.invoices.index', icon: Receipt },
            ],
        },
        paymentRun: {
            label: 'Payment Run', icon: Play, routePrefix: 'finance.payment-run',
            work: [{ label: 'Payment Run', route: 'finance.payment-run.index', icon: Play }],
            quickLinks: [
                { label: 'Supplier Payments', route: 'finance.payments.index', icon: Rows3 },
                { label: 'Supplier Invoices', route: 'purchasing.invoices.index', icon: FileText },
            ],
        },
        payments: {
            label: 'Supplier Payments', icon: Rows3, routePrefix: 'finance.payments',
            work: [
                { label: 'Payments & Ageing', route: 'finance.payments.index', icon: LayoutList },
                { label: 'New Payment', route: 'finance.payments.create', icon: Plus },
                { label: 'Payment Run', route: 'finance.payment-run.index', icon: Play },
            ],
            quickLinks: [
                { label: 'Suppliers', route: 'suppliers.index', icon: Rows3 },
                { label: 'Supplier Invoices', route: 'purchasing.invoices.index', icon: FileText },
            ],
        },
        vat: {
            label: 'VAT Returns', icon: Percent, routePrefix: 'finance.vat',
            work: [{ label: 'VAT Returns', route: 'finance.vat.index', icon: Percent }],
            quickLinks: [
                { label: 'GL Enquiry', route: 'finance.gl.index', icon: BookOpen },
                { label: 'Trial Balance', route: 'finance.reports.trial-balance', icon: Columns2 },
            ],
        },
        reports: {
            label: 'Financial Reports', icon: Columns2, routePrefix: 'finance.reports',
            work: [
                { label: 'Trial Balance', route: 'finance.reports.trial-balance', icon: Columns2 },
                { label: 'Income Statement', route: 'finance.reports.income-statement', icon: TrendingUp },
                { label: 'Balance Sheet', route: 'finance.reports.balance-sheet', icon: Landmark },
            ],
            quickLinks: [
                { label: 'GL Enquiry', route: 'finance.gl.index', icon: BookOpen },
                { label: 'VAT (2210/2220)', route: 'finance.gl.index', icon: Percent },
            ],
        },
    },
};

export default navConfig;
