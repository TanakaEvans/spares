<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Part;
use App\Models\PriceList;
use App\Models\PurchaseOrder;
use App\Models\SalesDocument;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\Technician;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Services\ApPaymentService;
use App\Services\ArReceiptService;
use App\Services\GrnPostingService;
use App\Services\JobCardService;
use App\Services\OpeningBalanceService;
use App\Services\PricingService;
use App\Services\SalesPostingService;
use App\Services\SalesQuoteService;
use App\Services\SupplierInvoiceService;
use App\Services\WorkshopInvoiceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Volume demo data across every section for stress-testing (dev only).
 * Non-financial master data is bulk-inserted; every financial transaction posts
 * through the real services so AR/AP/stock/GL stay reconciled and System Health
 * stays green. Idempotent-lite via a count guard.
 */
class DemoDataSeeder extends Seeder
{
    // Tune volumes here.
    private const CUSTOMERS = 1000;

    private const SUPPLIERS = 200;

    private const VEHICLES = 800;

    private const STOCK_PARTS = 4000;

    private const QUOTES = 500;

    private const CASH_SALES = 900;

    private const ACCOUNT_SALES = 250;

    private const CREDIT_NOTES = 120;

    private const PURCH_CHAINS = 130;

    private const AP_PAYMENTS = 90;

    private const JOBS = 220;

    private const SERIALS = 900;

    private const LOYALTY = 800;

    private const COMMS = 900;

    private const LAYBYS = 120;

    private const WARRANTY = 100;

    private const BANK_LINES = 400;

    private const IMPORTS = 60;

    private const DELIVERIES = 200;

    private const BULLETINS = 60;

    private const PROMOS = 40;

    private int $ok = 0;

    private int $fail = 0;

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('DemoDataSeeder is blocked in production.');

            return;
        }

        @set_time_limit(0);
        DB::disableQueryLog();

        $this->command?->info('Seeding master data…');
        $branches = Branch::pluck('id')->all();
        $mainBranch = Branch::where('is_main_branch', true)->value('id') ?? $branches[0];

        $this->seedCustomers($branches);
        $this->seedSuppliers();
        $this->seedVehicles($branches);
        $this->seedFeatureData($branches);
        $this->command?->info('  master data done.');

        $this->command?->info('Seeding opening stock…');
        $this->seedStock($mainBranch);
        app(OpeningBalanceService::class)->postOpeningInventory();

        $this->command?->info('Posting sales (this takes a moment)…');
        $this->seedSales($mainBranch);

        $this->command?->info('Posting purchasing…');
        $this->seedPurchasing($mainBranch);

        $this->command?->info('Posting workshop jobs…');
        $this->seedWorkshop($mainBranch);

        $this->command?->info(sprintf('Done. %d postings OK, %d skipped (e.g. out of stock).', $this->ok, $this->fail));
        $this->command?->info('Run System Health to confirm the books still reconcile.');
    }

    private function pick(array $a): mixed
    {
        return $a[array_rand($a)];
    }

    // ── Master data ──────────────────────────────────────────────────────

    private function seedCustomers(array $branches): void
    {
        if (DB::table('customers')->where('customer_number', 'like', 'CUST-%')->count() > 100) {
            return; // already seeded
        }
        $groups = CustomerGroup::pluck('id')->all();
        $retail = PriceList::where('is_default', true)->value('id');
        $trade = PriceList::where('type', 'trade')->value('id') ?? $retail;
        $types = ['individual', 'business', 'fleet', 'dealer'];
        $cities = ['Harare', 'Bulawayo', 'Gweru', 'Mutare', 'Masvingo', 'Kwekwe', 'Chitungwiza', 'Johannesburg', 'Pretoria', 'Gaborone'];

        $rows = [];
        for ($i = 1; $i <= self::CUSTOMERS; $i++) {
            $type = $this->pick($types);
            $business = in_array($type, ['business', 'fleet', 'dealer'], true);
            $name = $business ? fake()->company() : fake()->name();
            $rows[] = [
                'customer_number' => 'CUST-'.str_pad((string) (100000 + $i), 6, '0', STR_PAD_LEFT),
                'type' => $type, 'name' => $name,
                'trading_name' => $business ? fake()->companySuffix() : null,
                'vat_number' => $business ? (string) fake()->numberBetween(2000000000, 2999999999) : null,
                'email' => fake()->safeEmail(), 'phone' => '+263 7'.fake()->numberBetween(1, 8).' '.fake()->numberBetween(1000000, 9999999),
                'city' => $this->pick($cities),
                'customer_group_id' => $business && $groups ? $this->pick($groups) : null,
                'price_list_id' => $business ? $trade : $retail,
                'payment_terms_days' => $business ? $this->pick([0, 30, 30, 60]) : 0,
                'credit_limit' => $business ? fake()->numberBetween(500, 20000) : 0,
                'on_hold' => $business && fake()->boolean(6), 'hold_reason' => null,
                'is_walk_in' => false, 'is_active' => fake()->boolean(95), 'notes' => null,
                'created_at' => now(), 'updated_at' => now(),
            ];
        }
        foreach (array_chunk($rows, 500) as $c) {
            DB::table('customers')->insert($c);
        }
    }

    private function seedSuppliers(): void
    {
        if (DB::table('suppliers')->where('supplier_number', 'like', 'SUPP-1%')->count() > 50) {
            return; // already seeded
        }
        $suppliers = [];
        for ($i = 1; $i <= self::SUPPLIERS; $i++) {
            $suppliers[] = [
                'supplier_number' => 'SUPP-'.str_pad((string) (10000 + $i), 5, '0', STR_PAD_LEFT),
                'name' => fake()->company().' '.$this->pick(['Spares', 'Motors', 'Auto', 'Parts', 'Distributors', 'Trading']),
                'type' => $this->pick(['local', 'import', 'manufacturer']),
                'payment_terms_days' => $this->pick([0, 30, 30, 45, 60]),
                'credit_limit' => fake()->numberBetween(0, 50000), 'lead_time_days' => fake()->numberBetween(1, 45),
                'minimum_order_value' => $this->pick([0, 0, 100, 500]),
                'email' => fake()->companyEmail(), 'phone' => '+263 '.fake()->numberBetween(20, 29).' '.fake()->numberBetween(100000, 999999),
                'city' => $this->pick(['Harare', 'Bulawayo', 'Johannesburg', 'Durban', 'Guangzhou', 'Dubai']),
                'is_active' => fake()->boolean(95), 'created_at' => now(), 'updated_at' => now(),
            ];
        }
        foreach (array_chunk($suppliers, 500) as $c) {
            DB::table('suppliers')->insert($c);
        }

        // Contacts + approved suppliers.
        $supplierIds = Supplier::pluck('id')->all();
        $contacts = [];
        foreach ($supplierIds as $sid) {
            $n = fake()->numberBetween(1, 3);
            for ($j = 0; $j < $n; $j++) {
                $contacts[] = ['supplier_id' => $sid, 'name' => fake()->name(), 'position' => $this->pick(['Sales', 'Accounts', 'Manager', 'Rep']),
                    'email' => fake()->safeEmail(), 'phone' => '+263 7'.fake()->numberBetween(1000000, 9999999), 'is_primary' => $j === 0, 'created_at' => now(), 'updated_at' => now()];
            }
        }
        foreach (array_chunk($contacts, 500) as $c) {
            DB::table('supplier_contacts')->insert($c);
        }

        $partIds = Part::inRandomOrder()->limit(1500)->pluck('id')->all();
        $approved = [];
        $seen = [];
        for ($i = 0; $i < 800; $i++) {
            $pid = $this->pick($partIds);
            $sid = $this->pick($supplierIds);
            $key = $pid.'-'.$sid;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $approved[] = ['part_id' => $pid, 'supplier_id' => $sid, 'is_preferred' => fake()->boolean(25), 'lead_time_days' => fake()->numberBetween(1, 30), 'created_at' => now(), 'updated_at' => now()];
        }
        foreach (array_chunk($approved, 500) as $c) {
            DB::table('approved_suppliers')->insert($c);
        }
    }

    private function seedVehicles(array $branches): void
    {
        if (DB::table('customer_vehicles')->count() > 100) {
            return; // already seeded
        }
        $customerIds = Customer::where('is_walk_in', false)->pluck('id')->all();
        $models = VehicleModel::with('make:id')->get(['id', 'make_id', 'year_from', 'year_to'])->all();
        $colours = ['White', 'Silver', 'Black', 'Grey', 'Blue', 'Red', 'Green', 'Gold'];
        $rows = [];
        for ($i = 1; $i <= self::VEHICLES; $i++) {
            $m = $this->pick($models);
            $rows[] = ['customer_id' => $this->pick($customerIds), 'make_id' => $m->make_id, 'model_id' => $m->id,
                'branch_id' => $this->pick($branches),
                'registration' => strtoupper(fake()->bothify('???-####')), 'year' => fake()->numberBetween($m->year_from ?: 2000, (int) date('Y')),
                'colour' => $this->pick($colours), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()];
        }
        foreach (array_chunk($rows, 500) as $c) {
            DB::table('customer_vehicles')->insert($c);
        }
    }

    private function seedFeatureData(array $branches): void
    {
        if (DB::table('loyalty_entries')->count() > 100) {
            return; // already seeded
        }
        $customerIds = Customer::where('is_walk_in', false)->pluck('id')->all();
        $partIds = Part::inRandomOrder()->limit(2000)->pluck('id')->all();
        $makeIds = VehicleMake::pluck('id')->all();
        $now = now();
        $ins = fn (string $table, array $rows) => collect(array_chunk($rows, 500))->each(fn ($c) => DB::table($table)->insert($c));

        // Technicians
        $techs = [];
        for ($i = 0; $i < 20; $i++) {
            $techs[] = ['name' => fake()->name(), 'skill_level' => $this->pick(['apprentice', 'qualified', 'qualified', 'master']),
                'specialisations' => $this->pick(['Engines', 'Brakes', 'Electrical', 'Transmissions', 'Diagnostics']),
                'cost_rate' => fake()->numberBetween(6, 15), 'is_active' => true, 'created_at' => $now, 'updated_at' => $now];
        }
        $ins('technicians', $techs);

        // Comms log
        $comms = [];
        for ($i = 0; $i < self::COMMS; $i++) {
            $comms[] = ['customer_id' => $this->pick($customerIds), 'channel' => $this->pick(['call', 'email', 'visit', 'note']),
                'subject' => $this->pick(['Follow-up on order', 'Quote request', 'Payment reminder', 'Delivery query', 'Complaint', 'New enquiry']),
                'body' => fake()->sentence(), 'created_at' => fake()->dateTimeBetween('-6 months'), 'updated_at' => $now];
        }
        $ins('customer_notes', $comms);

        // Loyalty
        $loyal = [];
        for ($i = 0; $i < self::LOYALTY; $i++) {
            $loyal[] = ['customer_id' => $this->pick($customerIds), 'points' => fake()->numberBetween(-200, 500), 'reason' => $this->pick(['Purchase', 'Redemption', 'Bonus', 'Adjustment']), 'created_at' => fake()->dateTimeBetween('-1 year'), 'updated_at' => $now];
        }
        $ins('loyalty_entries', $loyal);

        // Serials
        $serials = [];
        for ($i = 0; $i < self::SERIALS; $i++) {
            $serials[] = ['part_id' => $this->pick($partIds), 'branch_id' => $this->pick($branches), 'serial' => strtoupper(fake()->bothify('SN-########')),
                'batch' => fake()->boolean(40) ? strtoupper(fake()->bothify('B####')) : null, 'status' => $this->pick(['in_stock', 'in_stock', 'sold', 'returned']), 'created_at' => $now, 'updated_at' => $now];
        }
        $ins('serial_numbers', $serials);

        // Bulletins
        $bulletins = [];
        for ($i = 0; $i < self::BULLETINS; $i++) {
            $bulletins[] = ['title' => fake()->sentence(5), 'make_id' => fake()->boolean(70) ? $this->pick($makeIds) : null,
                'category' => $this->pick(['service_note', 'fitment_warning', 'recall']), 'body' => fake()->paragraph(), 'is_active' => true, 'created_at' => fake()->dateTimeBetween('-1 year'), 'updated_at' => $now];
        }
        $ins('technical_bulletins', $bulletins);

        // Promotions
        $catIds = DB::table('part_categories')->pluck('id')->all();
        $promos = [];
        for ($i = 0; $i < self::PROMOS; $i++) {
            $all = fake()->boolean(50);
            $promos[] = ['name' => $this->pick(['Winter', 'Summer', 'Month-End', 'Clearance', 'Fleet', 'Trade']).' '.$this->pick(['Special', 'Deal', 'Promo', 'Sale']),
                'type' => $this->pick(['percent', 'fixed']), 'value' => fake()->numberBetween(5, 25), 'applies_to' => $all ? 'all' : 'category',
                'category_id' => $all ? null : $this->pick($catIds), 'starts_at' => fake()->dateTimeBetween('-2 months'), 'ends_at' => fake()->dateTimeBetween('now', '+2 months'),
                'is_active' => fake()->boolean(70), 'created_at' => $now, 'updated_at' => $now];
        }
        $ins('promotions', $promos);

        // Bank accounts + statement lines
        $bankIds = [];
        foreach ([['Main Current', '1120'], ['Savings', '1130'], ['Petty Cash Float', '1110']] as [$bn, $code]) {
            $gl = DB::table('gl_accounts')->where('account_code', $code)->value('id');
            $bankIds[] = DB::table('bank_accounts')->insertGetId(['name' => $bn, 'gl_account_id' => $gl, 'account_number' => fake()->bankAccountNumber(), 'bank_name' => $this->pick(['CBZ', 'Stanbic', 'FBC', 'Nedbank']), 'currency' => 'USD', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }
        $lines = [];
        for ($i = 0; $i < self::BANK_LINES; $i++) {
            $lines[] = ['bank_account_id' => $this->pick($bankIds), 'txn_date' => fake()->dateTimeBetween('-3 months'), 'description' => $this->pick(['Deposit', 'EFT payment', 'Bank charges', 'Customer receipt', 'Supplier payment', 'Cash deposit']),
                'amount' => fake()->randomFloat(2, -5000, 5000), 'reconciled' => fake()->boolean(60), 'created_at' => $now, 'updated_at' => $now];
        }
        $ins('bank_statement_lines', $lines);

        // Import shipments
        $supplierIds = Supplier::pluck('id')->all();
        $imports = [];
        for ($i = 1; $i <= self::IMPORTS; $i++) {
            $goods = fake()->numberBetween(2000, 40000);
            $imports[] = ['shipment_ref' => 'IMP-'.date('Y').'-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), 'supplier_id' => $this->pick($supplierIds),
                'origin_country' => $this->pick(['China', 'Japan', 'South Africa', 'UAE', 'Germany']), 'status' => $this->pick(['ordered', 'in_transit', 'customs', 'cleared', 'received']),
                'eta' => fake()->dateTimeBetween('-1 month', '+2 months'), 'goods_value' => $goods, 'freight' => round($goods * 0.08, 2), 'duty' => round($goods * 0.25, 2), 'created_at' => $now, 'updated_at' => $now];
        }
        $ins('import_shipments', $imports);

        // Scheduled reports
        $sched = [];
        foreach (['Daily Sales Summary' => 'sales_summary', 'Weekly Reorder' => 'stock_reorder', 'Monthly AR Ageing' => 'ar_ageing', 'Monthly P&L' => 'income_statement', 'Top Customers' => 'top_customers'] as $name => $key) {
            $sched[] = ['name' => $name, 'report_key' => $key, 'frequency' => $this->pick(['daily', 'weekly', 'monthly']), 'run_time' => '08:00', 'recipients' => fake()->safeEmail(), 'format' => 'pdf', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now];
        }
        $ins('scheduled_reports', $sched);

        // Delivery notes
        $deliveries = [];
        for ($i = 1; $i <= self::DELIVERIES; $i++) {
            $deliveries[] = ['delivery_number' => 'DN-'.date('Ymd').'-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), 'customer_id' => $this->pick($customerIds), 'branch_id' => $this->pick($branches),
                'delivery_date' => fake()->dateTimeBetween('-2 months'), 'driver' => fake()->name(), 'vehicle_reg' => strtoupper(fake()->bothify('???-###')), 'status' => $this->pick(['dispatched', 'delivered', 'delivered']), 'created_at' => $now, 'updated_at' => $now];
        }
        $ins('delivery_notes', $deliveries);

        // Warranty claims
        $warranty = [];
        for ($i = 1; $i <= self::WARRANTY; $i++) {
            $claim = fake()->numberBetween(20, 400);
            $status = $this->pick(['draft', 'submitted', 'acknowledged', 'approved', 'rejected', 'credited']);
            $warranty[] = ['claim_number' => 'WC-'.date('Y').'-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), 'supplier_id' => $this->pick($supplierIds), 'part_id' => $this->pick($partIds),
                'status' => $status, 'fault' => fake()->sentence(), 'claim_amount' => $claim, 'credit_amount' => $status === 'credited' ? $claim : 0, 'created_at' => fake()->dateTimeBetween('-6 months'), 'updated_at' => $now];
        }
        $ins('warranty_claims', $warranty);

        // Lay-bys + payments
        for ($i = 1; $i <= self::LAYBYS; $i++) {
            $total = fake()->numberBetween(100, 3000);
            $deposit = round($total * fake()->randomFloat(2, 0.2, 1.0), 2);
            $lid = DB::table('laybys')->insertGetId(['layby_number' => 'LB-'.date('Ymd').'-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), 'customer_id' => $this->pick($customerIds), 'branch_id' => $this->pick($branches),
                'total' => $total, 'deposit_paid' => $deposit, 'status' => $deposit >= $total ? 'completed' : 'active', 'created_at' => fake()->dateTimeBetween('-3 months'), 'updated_at' => $now]);
            DB::table('layby_payments')->insert(['layby_id' => $lid, 'amount' => $deposit, 'method' => 'cash', 'paid_on' => now()->toDateString(), 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    // ── Opening stock (raw + reconciling journal) ────────────────────────

    private function seedStock(int $branch): void
    {
        $alreadyStocked = DB::table('stock_levels')->where('branch_id', $branch)->pluck('part_id')->flip();
        $priced = DB::table('price_list_items as pi')
            ->join('price_lists as pl', fn ($j) => $j->on('pl.id', '=', 'pi.price_list_id')->where('pl.is_default', true))
            ->inRandomOrder()->limit(self::STOCK_PARTS)->get(['pi.part_id', 'pi.price']);

        $ledger = [];
        $levels = [];
        foreach ($priced as $p) {
            if (isset($alreadyStocked[$p->part_id])) {
                continue; // don't duplicate an existing stock level
            }
            $alreadyStocked[$p->part_id] = true;
            $cost = round((float) $p->price / 2, 4);
            $qty = fake()->numberBetween(3, 120);
            $ledger[] = ['part_id' => $p->part_id, 'branch_id' => $branch, 'transaction_type' => 'OPENING_BALANCE', 'qty' => $qty, 'unit_cost' => $cost,
                'running_balance' => $qty, 'reference_type' => 'DemoSeeder', 'reference_id' => 0, 'notes' => 'Demo opening', 'user_id' => null, 'created_at' => now(), 'updated_at' => now()];
            $levels[] = ['part_id' => $p->part_id, 'branch_id' => $branch, 'qty_on_hand' => $qty, 'qty_reserved' => 0, 'qty_on_order' => 0, 'qty_in_transit' => 0,
                'average_cost' => $cost, 'reorder_point' => fake()->numberBetween(0, 10), 'reorder_qty' => fake()->numberBetween(5, 40), 'last_movement_at' => now(), 'created_at' => now(), 'updated_at' => now()];
        }
        foreach (array_chunk($ledger, 1000) as $c) {
            DB::table('stock_ledger')->insert($c);
        }
        foreach (array_chunk($levels, 1000) as $c) {
            DB::table('stock_levels')->insert($c);
        }
    }

    // ── Financial via services ───────────────────────────────────────────

    private function stockedFastMovers(int $branch, int $limit = 400): array
    {
        return DB::table('stock_levels as sl')
            ->join('price_list_items as pi', 'pi.part_id', '=', 'sl.part_id')
            ->join('price_lists as pl', fn ($j) => $j->on('pl.id', '=', 'pi.price_list_id')->where('pl.is_default', true))
            ->where('sl.branch_id', $branch)->where('sl.qty_on_hand', '>', 20)
            ->inRandomOrder()->limit($limit)
            ->get(['sl.part_id', 'pi.price'])->map(fn ($r) => ['id' => $r->part_id, 'price' => (float) $r->price])->all();
    }

    private function seedSales(int $branch): void
    {
        $sales = app(SalesPostingService::class);
        $quotes = app(SalesQuoteService::class);
        $ar = app(ArReceiptService::class);
        $pricing = app(PricingService::class);
        if (SalesDocument::where('document_type', 'invoice')->count() > 1000) {
            return; // sales already seeded
        }
        $walkIn = Customer::walkIn();
        $accountCustomers = Customer::where('is_walk_in', false)->where('credit_limit', '>', 1000)->where('on_hold', false)->inRandomOrder()->limit(300)->get();
        $parts = $this->stockedFastMovers($branch, 500);
        if ($parts === []) {
            return;
        }

        // Quotes (+ convert some to orders) — no GL.
        for ($i = 0; $i < self::QUOTES; $i++) {
            try {
                $cust = fake()->boolean(60) && $accountCustomers->isNotEmpty() ? $accountCustomers->random() : $walkIn;
                $lines = $this->randomLines($parts, $pricing, $cust);
                $quote = $quotes->createQuote($cust, $branch, $lines);
                if (fake()->boolean(35)) {
                    $order = $quotes->convertToOrder($quote->fresh(['lines']));
                    if (fake()->boolean(50)) {
                        $sales->postInvoice($cust, $branch, $this->orderLines($order), [['method' => 'account', 'amount' => (float) $order->total_incl]], null, $order);
                    }
                }
                $this->ok++;
            } catch (\Throwable) {
                $this->fail++;
            }
        }

        // Cash sales.
        for ($i = 0; $i < self::CASH_SALES; $i++) {
            try {
                $lines = $this->randomLines($parts, $pricing, null);
                $total = $this->linesTotal($lines, $pricing);
                $sales->postInvoice($walkIn, $branch, $lines, [['method' => fake()->boolean(70) ? 'cash' : 'card', 'amount' => $total, 'tendered' => $total]]);
                $this->ok++;
            } catch (\Throwable) {
                $this->fail++;
            }
        }

        // Account sales.
        $accountInvoices = [];
        for ($i = 0; $i < self::ACCOUNT_SALES && $accountCustomers->isNotEmpty(); $i++) {
            try {
                $cust = $accountCustomers->random();
                $lines = $this->randomLines($parts, $pricing, $cust);
                $total = $this->linesTotal($lines, $pricing);
                if ($cust->fresh()->arBalance() + $total > (float) $cust->credit_limit) {
                    continue;
                }
                $inv = $sales->postInvoice($cust, $branch, $lines, [['method' => 'account', 'amount' => $total]]);
                $accountInvoices[] = [$cust->id, $inv->id, $total];
                $this->ok++;
            } catch (\Throwable) {
                $this->fail++;
            }
        }

        // Receipts against ~70% of account invoices.
        foreach ($accountInvoices as [$custId, $invId, $total]) {
            if (! fake()->boolean(70)) {
                continue;
            }
            try {
                $ar->postReceipt(Customer::find($custId), $branch, $this->pick(['eft', 'cash', 'bank_transfer']), $total, [['document_id' => $invId, 'amount' => $total]]);
                $this->ok++;
            } catch (\Throwable) {
                $this->fail++;
            }
        }

        // Credit notes against random cash invoices.
        $invoiceIds = SalesDocument::where('document_type', 'invoice')->where('branch_id', $branch)->inRandomOrder()->limit(self::CREDIT_NOTES * 2)->pluck('id')->all();
        $done = 0;
        foreach ($invoiceIds as $id) {
            if ($done >= self::CREDIT_NOTES) {
                break;
            }
            try {
                $inv = SalesDocument::with('lines', 'customer')->find($id);
                $line = $inv->lines->firstWhere('line_type', 'part') ?? $inv->lines->first();
                if (! $line || (float) $line->qty < 1) {
                    continue;
                }
                // A cash/walk-in sale can only be refunded in cash — never as an account credit
                // (mirrors the "walk-in cannot buy on account" rule; keeps 1210 reconciled).
                $mode = ($inv->customer && ! $inv->customer->is_walk_in && fake()->boolean(40)) ? 'account' : 'refund_cash';
                $sales->postCreditNote($inv, [['line_id' => $line->id, 'qty' => 1, 'restock' => fake()->boolean(80)]], $mode, $this->pick(['Wrong part', 'Faulty', 'Customer changed mind', 'Over-supplied']));
                $done++;
                $this->ok++;
            } catch (\Throwable) {
                $this->fail++;
            }
        }
    }

    private function randomLines(array $parts, PricingService $pricing, ?Customer $cust): array
    {
        $n = fake()->numberBetween(1, 4);
        $lines = [];
        $used = [];
        for ($j = 0; $j < $n; $j++) {
            $p = $this->pick($parts);
            if (isset($used[$p['id']])) {
                continue;
            }
            $used[$p['id']] = true;
            $lines[] = ['part_id' => $p['id'], 'qty' => fake()->numberBetween(1, 3), 'unit_price' => $p['price'], 'discount_pct' => fake()->boolean(15) ? fake()->numberBetween(5, 10) : 0];
        }

        return $lines;
    }

    private function orderLines(SalesDocument $order): array
    {
        return $order->lines->map(fn ($l) => ['part_id' => $l->part_id, 'qty' => (float) $l->qty, 'unit_price' => (float) $l->unit_price, 'discount_pct' => (float) $l->discount_pct, 'description' => $l->description])->all();
    }

    private function linesTotal(array $lines, PricingService $pricing): float
    {
        $t = 0.0;
        foreach ($lines as $l) {
            $t += $pricing->computeLine((float) $l['qty'], (float) $l['unit_price'], (float) ($l['discount_pct'] ?? 0))['line_total_incl'];
        }

        return round($t, 2);
    }

    private function seedPurchasing(int $branch): void
    {
        $grnSvc = app(GrnPostingService::class);
        $invSvc = app(SupplierInvoiceService::class);
        $apSvc = app(ApPaymentService::class);
        if (DB::table('supplier_invoices')->count() > 100) {
            return; // purchasing already seeded
        }
        $suppliers = Supplier::where('is_active', true)->inRandomOrder()->limit(80)->get();
        $parts = Part::inRandomOrder()->limit(400)->pluck('id')->all();
        if ($suppliers->isEmpty()) {
            return;
        }

        for ($i = 0; $i < self::PURCH_CHAINS; $i++) {
            try {
                $supplier = $suppliers->random();
                $po = PurchaseOrder::create(['po_number' => 'PO-'.date('Ymd').'-'.uniqid(), 'supplier_id' => $supplier->id, 'branch_id' => $branch, 'status' => 'submitted', 'order_date' => now()->toDateString(), 'subtotal' => 0, 'total' => 0]);
                $sub = 0.0;
                $recvLines = [];
                $n = fake()->numberBetween(1, 4);
                for ($j = 0; $j < $n; $j++) {
                    $cost = fake()->randomFloat(2, 2, 60);
                    $qty = fake()->numberBetween(5, 50);
                    $poLine = $po->lines()->create(['part_id' => $this->pick($parts), 'description' => 'Stock purchase', 'qty_ordered' => $qty, 'unit_cost' => $cost, 'line_total' => round($qty * $cost, 2)]);
                    $sub += $qty * $cost;
                    $recvLines[] = ['po_line_id' => $poLine->id, 'qty_received' => $qty, 'qty_rejected' => 0];
                }
                $po->update(['status' => 'submitted', 'subtotal' => round($sub, 2), 'total' => round($sub, 2)]);
                $grn = $grnSvc->receive($po->fresh(), $recvLines);
                $vat = round($sub * 0.15, 2);
                $inv = $invSvc->capture($grn, 'INV'.fake()->numberBetween(1000, 99999), now()->toDateString(), round($sub, 2), $vat);
                $invSvc->post($inv);
                $this->ok++;
            } catch (\Throwable) {
                $this->fail++;
            }
        }

        // AP payments against ~70% of posted supplier invoices.
        $paid = 0;
        foreach (SupplierInvoice::where('status', 'posted')->inRandomOrder()->get() as $inv) {
            if ($paid >= self::AP_PAYMENTS) {
                break;
            }
            if (! fake()->boolean(70)) {
                continue;
            }
            try {
                $apSvc->postPayment(Supplier::find($inv->supplier_id), $branch, $this->pick(['eft', 'bank_transfer']), (float) $inv->total, [['supplier_invoice_id' => $inv->id, 'amount' => (float) $inv->total]]);
                $paid++;
                $this->ok++;
            } catch (\Throwable) {
                $this->fail++;
            }
        }
    }

    private function seedWorkshop(int $branch): void
    {
        if (DB::table('job_cards')->count() > 150) {
            return; // workshop already seeded
        }
        $jobSvc = app(JobCardService::class);
        $invSvc = app(WorkshopInvoiceService::class);
        $techIds = Technician::pluck('id')->all();
        $customers = Customer::where('is_walk_in', false)->has('receipts', '>=', 0)->inRandomOrder()->limit(200)->get();
        $vehiclesByCustomer = DB::table('customer_vehicles')->select('id', 'customer_id')->get()->groupBy('customer_id');
        $labourCodeIds = DB::table('labour_codes')->pluck('id')->all();
        $parts = $this->stockedFastMovers($branch, 200);

        for ($i = 0; $i < self::JOBS; $i++) {
            try {
                $cust = $customers->random();
                $veh = $vehiclesByCustomer[$cust->id][0]->id ?? null;
                $job = $jobSvc->create($branch, ['customer_id' => $cust->id, 'vehicle_id' => $veh, 'reported_fault' => fake()->sentence(), 'odometer_in' => fake()->numberBetween(20000, 250000)], null);
                if ($techIds) {
                    $jobSvc->assignTechnician($job, $this->pick($techIds));
                }
                $jobSvc->transition($job->fresh(), 'in_progress');
                // Labour
                $jobSvc->addLabour($job->fresh(), ['labour_code_id' => $labourCodeIds ? $this->pick($labourCodeIds) : null, 'hours' => fake()->randomFloat(1, 0.5, 4)]);
                // Parts (issue 1-2 if available)
                if ($parts) {
                    for ($k = 0; $k < fake()->numberBetween(0, 2); $k++) {
                        $p = $this->pick($parts);
                        $line = $jobSvc->requestPart($job->fresh(), ['part_id' => $p['id'], 'qty' => 1, 'unit_price' => $p['price']]);
                        $jobSvc->issuePart($line->fresh());
                    }
                }
                // Progress most to completed + invoice ~55%.
                if (fake()->boolean(80)) {
                    $jobSvc->transition($job->fresh(), 'quality_check');
                    $jobSvc->transition($job->fresh(), 'completed');
                    if (fake()->boolean(60)) {
                        // Empty payments → service bills the full computed total on account (always balances).
                        $invSvc->invoiceJob($job->fresh(['labours', 'parts', 'customer']));
                    }
                }
                $this->ok++;
            } catch (\Throwable) {
                $this->fail++;
            }
        }
    }
}
