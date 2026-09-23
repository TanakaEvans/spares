<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Company;
use App\Services\DocumentIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentIdentityServiceTest extends TestCase
{
    use RefreshDatabase;

    private DocumentIdentityService $identity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->identity = app(DocumentIdentityService::class);
    }

    public function test_company_identity_without_branch(): void
    {
        Company::factory()->create([
            'name' => 'SparesPro Motors',
            'vat_number' => 'VAT-12345',
            'phone' => '+263 77 000 0000',
            'bank_name' => 'CBZ Bank',
            'bank_account_number' => '1002003004',
        ]);

        $identity = $this->identity->for();

        $this->assertSame('SparesPro Motors', $identity['name']);
        $this->assertSame('VAT-12345', $identity['vat_number']);
        $this->assertSame('CBZ Bank', $identity['bank_name']);
        $this->assertNull($identity['branch_name']);
    }

    public function test_branch_fields_overlay_company_fields(): void
    {
        $company = Company::factory()->create([
            'phone' => '+263 77 000 0000',
            'city' => 'Harare',
            'bank_name' => 'CBZ Bank',
        ]);
        $branch = Branch::factory()->create([
            'company_id' => $company->id,
            'name' => 'Bulawayo Branch',
            'phone' => '+263 78 111 1111',
            'city' => 'Bulawayo',
            'bank_name' => null,
        ]);

        $identity = $this->identity->for($branch);

        // Branch overrides where it has values…
        $this->assertSame('Bulawayo Branch', $identity['branch_name']);
        $this->assertSame('+263 78 111 1111', $identity['phone']);
        $this->assertSame('Bulawayo', $identity['city']);
        // …and falls back to the company where it doesn't.
        $this->assertSame('CBZ Bank', $identity['bank_name']);
    }

    public function test_statutory_fields_always_come_from_the_company(): void
    {
        $company = Company::factory()->create(['vat_number' => 'VAT-99']);
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        $this->assertSame('VAT-99', $this->identity->for($branch)['vat_number']);
    }
}
