<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\GoodsReceivedNote;
use App\Models\ImportShipment;
use App\Models\Part;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierReturn;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The four purchasing detail pages (GRN, supplier invoice, supplier return,
 * import shipment) render with their procure-to-pay links resolved by id.
 */
class PurchasingDetailPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Branch $branch;

    private Supplier $supplier;

    private Part $part;

    private PurchaseOrder $po;

    private GoodsReceivedNote $grn;

    protected function setUp(): void
    {
        parent::setUp();

        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $role = Role::create(['name' => 'Superuser', 'description' => 'Superuser']);
        $this->user = User::factory()->create(['password_changed_at' => now()]);
        $this->user->roles()->attach($role->id, ['created_at' => now(), 'updated_at' => now()]);

        $this->branch = Branch::factory()->create();
        $this->supplier = Supplier::create([
            'supplier_number' => 'SUPP-00001',
            'name' => 'GUD Distributors',
            'payment_terms_days' => 30,
        ]);
        $this->part = Part::factory()->create(['part_number' => 'Z762']);

        $this->po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-0001',
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'buyer_id' => $this->user->id,
            'status' => 'received',
            'order_date' => now()->toDateString(),
            'subtotal' => 68,
            'total' => 68,
        ]);
        $poLine = $this->po->lines()->create([
            'part_id' => $this->part->id,
            'description' => 'Oil Filter',
            'qty_ordered' => 20,
            'unit_cost' => 3.40,
            'line_total' => 68,
            'qty_received' => 20,
        ]);

        $this->grn = GoodsReceivedNote::create([
            'grn_number' => 'GRN-TEST-0001',
            'po_id' => $this->po->id,
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'received_by' => $this->user->id,
            'received_date' => now()->toDateString(),
            'status' => 'posted',
            'posted_at' => now(),
        ]);
        $this->grn->lines()->create([
            'po_line_id' => $poLine->id,
            'part_id' => $this->part->id,
            'qty_received' => 20,
            'qty_rejected' => 0,
            'unit_cost' => 3.40,
        ]);
    }

    public function test_grn_detail_page_renders_with_its_chain(): void
    {
        $this->actingAs($this->user)
            ->get(route('purchasing.grns.show', $this->grn))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Purchasing/GRNs/Show')
                ->where('grn.grn_number', 'GRN-TEST-0001')
                ->where('grn.po_id', $this->po->id)
                ->where('grn.supplier_id', $this->supplier->id)
                ->where('grn.received_by_id', $this->user->id)
                ->where('grn.lines.0.part_id', $this->part->id)
                ->where('grn.accepted_value', 68));
    }

    public function test_supplier_invoice_detail_page_renders(): void
    {
        $invoice = SupplierInvoice::create([
            'invoice_number' => 'SINV-TEST-0001',
            'supplier_ref' => 'GUD-4471',
            'supplier_id' => $this->supplier->id,
            'grn_id' => $this->grn->id,
            'invoice_date' => now()->toDateString(),
            'subtotal' => 68,
            'vat_amount' => 10.20,
            'total' => 78.20,
            'status' => 'posted',
        ]);

        $this->actingAs($this->user)
            ->get(route('purchasing.invoices.show', $invoice))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Purchasing/Invoices/Show')
                ->where('invoice.supplier_ref', 'GUD-4471')
                ->where('invoice.grn_id', $this->grn->id)
                ->where('invoice.po_id', $this->po->id)
                ->where('invoice.outstanding', 78.2)
                ->where('invoice.lines.0.part_id', $this->part->id));
    }

    public function test_supplier_return_detail_page_renders(): void
    {
        $return = SupplierReturn::create([
            'return_number' => 'RTS-TEST-0001',
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'reason' => 'damaged',
            'status' => 'draft',
        ]);
        $return->lines()->create([
            'part_id' => $this->part->id,
            'qty' => 2,
            'unit_cost' => 3.40,
            'condition' => 'damaged',
        ]);

        $this->actingAs($this->user)
            ->get(route('purchasing.returns.show', $return))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Purchasing/Returns/Show')
                ->where('supplierReturn.return_number', 'RTS-TEST-0001')
                ->where('supplierReturn.supplier_id', $this->supplier->id)
                ->where('supplierReturn.lines.0.part_id', $this->part->id)
                ->where('supplierReturn.value', 6.8));
    }

    public function test_import_shipment_detail_page_renders(): void
    {
        $shipment = ImportShipment::create([
            'shipment_ref' => 'IMP-TEST-0001',
            'supplier_id' => $this->supplier->id,
            'origin_country' => 'China',
            'status' => 'in_transit',
            'goods_value' => 1000,
            'freight' => 150,
            'duty' => 50,
        ]);

        $this->actingAs($this->user)
            ->get(route('purchasing.imports.show', $shipment))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Purchasing/Imports/Show')
                ->where('shipment.shipment_ref', 'IMP-TEST-0001')
                ->where('shipment.supplier_id', $this->supplier->id)
                ->where('shipment.landed', 1200));
    }
}
