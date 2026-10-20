<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The in-app User Guide: a hub of step-by-step, page-by-page walkthroughs.
 * Each section's body is a Markdown file under resources/guides/manual/ so the
 * content is easy to keep current (AGENTS §13a — a feature is not done until
 * its guide is). The registry below is the single source of order + metadata.
 */
class GuideController extends Controller
{
    /**
     * Guide sections in reading order. `file` is the Markdown under
     * resources/guides/manual/; `steps` are the "How to…" headings shown on the
     * hub card so staff can jump straight to a task.
     */
    private function sections(): array
    {
        return [
            ['code' => 'getting-started', 'name' => 'Getting Started', 'icon' => 'Rocket', 'color' => 'bg-orange-600',
                'summary' => 'First login, your way around the system, and the order to set things up.',
                'steps' => ['Sign in & change your password', 'Find your way around', 'Set up the business', 'Load your data']],
            ['code' => 'data-upload', 'name' => 'Loading Your Data', 'icon' => 'Upload', 'color' => 'bg-slate-700',
                'summary' => 'Bring your real parts, stock, suppliers and customers in from Excel.',
                'steps' => ['Download a template', 'Fill it in Excel', 'Upload Parts & Stock', 'Post opening stock']],
            ['code' => 'sales', 'name' => 'Selling (POS & Invoices)', 'icon' => 'ShoppingCart', 'color' => 'bg-orange-600',
                'summary' => 'Serve a customer at the till, take payment, quote, and handle returns.',
                'steps' => ['Make a cash sale', 'Sell on account', 'Take part-payment', 'Quote → order → invoice', 'Credit note / return']],
            ['code' => 'customers', 'name' => 'Customers', 'icon' => 'Users', 'color' => 'bg-orange-600',
                'summary' => 'Add customers, set credit limits, take payments and send statements.',
                'steps' => ['Add a customer', 'Set a credit limit', 'Take an account payment', 'Send a statement']],
            ['code' => 'purchasing', 'name' => 'Buying & Receiving Stock', 'icon' => 'ClipboardList', 'color' => 'bg-blue-700',
                'summary' => 'Order from suppliers, receive goods, match the invoice and pay.',
                'steps' => ['Raise a purchase order', 'Receive goods (GRN)', 'Match the supplier invoice', 'Pay the supplier', 'Reorder low stock']],
            ['code' => 'inventory', 'name' => 'Stock & Parts', 'icon' => 'Package', 'color' => 'bg-blue-700',
                'summary' => 'Find parts, check stock, adjust, transfer and count.',
                'steps' => ['Find a part', 'Check stock & bins', 'Adjust / write off', 'Transfer between branches', 'Do a stock take']],
            ['code' => 'workshop', 'name' => 'Workshop', 'icon' => 'Wrench', 'color' => 'bg-amber-600',
                'summary' => 'Book a job, add labour and parts, finish and invoice it.',
                'steps' => ['Open a job card', 'Assign a technician', 'Add labour & parts', 'Complete & invoice']],
            ['code' => 'finance', 'name' => 'Money & Accounts', 'icon' => 'Landmark', 'color' => 'bg-emerald-700',
                'summary' => 'Receipts, supplier payments, the day\'s cash-up, VAT and reports.',
                'steps' => ['Daily cash-up', 'Record a receipt', 'Pay suppliers', 'Read the trial balance', 'VAT return']],
            ['code' => 'reports', 'name' => 'Reports & Dashboard', 'icon' => 'BarChart3', 'color' => 'bg-emerald-700',
                'summary' => 'The owner\'s dashboard and every report, and how to read them.',
                'steps' => ['Read the dashboard', 'Sales & stock reports', 'Export to Excel/PDF']],
            ['code' => 'system-admin', 'name' => 'Running the System', 'icon' => 'Settings', 'color' => 'bg-slate-700',
                'summary' => 'Users and roles, company details, settings, health and backups.',
                'steps' => ['Add a staff user', 'Set permissions', 'Company & branding', 'Check System Health', 'Back up']],
        ];
    }

    public function index(): Response
    {
        return Inertia::render('Guide/Index', [
            'sections' => array_map(fn ($s) => collect($s)->except('file')->all(), $this->sections()),
        ]);
    }

    public function show(string $section): Response
    {
        $def = collect($this->sections())->firstWhere('code', $section);
        if (! $def) {
            throw new NotFoundHttpException;
        }

        $path = resource_path("guides/manual/{$section}.md");
        $all = array_values($this->sections());
        $i = array_search($section, array_column($all, 'code'), true);

        return Inertia::render('Guide/Show', [
            'section' => collect($def)->except('file')->all(),
            'content' => File::exists($path) ? File::get($path) : "# {$def['name']}\n\nThis guide is being written.",
            'prev' => $i > 0 ? ['code' => $all[$i - 1]['code'], 'name' => $all[$i - 1]['name']] : null,
            'next' => $i < count($all) - 1 ? ['code' => $all[$i + 1]['code'], 'name' => $all[$i + 1]['name']] : null,
            'toc' => array_map(fn ($s) => ['code' => $s['code'], 'name' => $s['name'], 'icon' => $s['icon']], $all),
        ]);
    }
}
