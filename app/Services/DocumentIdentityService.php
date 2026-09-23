<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;

class DocumentIdentityService
{
    /**
     * The identity block for printed/emailed documents: company details
     * with branch-level fields overlaid where the branch has its own.
     * Captured once in System Admin, rendered everywhere — never re-typed.
     *
     * @return array{
     *     name: string, trading_name: ?string, logo: ?string,
     *     registration_number: ?string, tax_number: ?string, vat_number: ?string,
     *     branch_name: ?string, address: ?string, city: ?string, country: ?string,
     *     phone: ?string, email: ?string, website: ?string,
     *     bank_name: ?string, bank_branch_code: ?string,
     *     bank_account_name: ?string, bank_account_number: ?string,
     * }
     */
    public function for(Branch|int|null $branch = null): array
    {
        $company = Company::firstOrFail();

        if (is_int($branch)) {
            $branch = Branch::find($branch);
        }

        return [
            'name' => $company->name,
            'trading_name' => $company->trading_name,
            'logo' => $branch?->logo ?: $company->logo,
            'registration_number' => $company->registration_number,
            'tax_number' => $company->tax_number,
            'vat_number' => $company->vat_number,
            'branch_name' => $branch?->name,
            'address' => $branch?->address ?: $company->address,
            'city' => $branch?->city ?: $company->city,
            'country' => $company->country,
            'phone' => $branch?->phone ?: $company->phone,
            'email' => $branch?->email ?: $company->email,
            'website' => $company->website,
            'bank_name' => $branch?->bank_name ?: $company->bank_name,
            'bank_branch_code' => $branch?->bank_branch_code ?: $company->bank_branch_code,
            'bank_account_name' => $branch?->bank_account_name ?: $company->bank_account_name,
            'bank_account_number' => $branch?->bank_account_number ?: $company->bank_account_number,
        ];
    }
}
