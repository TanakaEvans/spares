<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Part;
use App\Models\PartCrossReference;
use App\Models\PartFitment;
use App\Models\PartSupersession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Cross-references, fitments and supersessions managed from the part
 * detail page's tabs (Modules 1.1/1.6/8.6).
 */
class PartRelationController extends Controller
{
    public function storeCrossReference(Request $request, Part $part): RedirectResponse
    {
        $data = $request->validate([
            'reference_number' => ['required', 'string', 'max:100'],
            'brand_id' => ['nullable', 'exists:part_brands,id'],
            'type' => ['required', 'in:oem,aftermarket,competitor,ean'],
        ]);

        $exists = $part->crossReferences()
            ->where('reference_number', $data['reference_number'])->exists();
        if ($exists) {
            return back()->withErrors(['reference_number' => 'That reference already exists on this part.']);
        }

        $part->crossReferences()->create($data);

        return back()->with('success', "Cross-reference {$data['reference_number']} added.");
    }

    public function destroyCrossReference(Part $part, PartCrossReference $crossReference): RedirectResponse
    {
        abort_unless($crossReference->part_id === $part->id, 404);
        $crossReference->delete();

        return back()->with('success', 'Cross-reference removed.');
    }

    public function storeFitment(Request $request, Part $part): RedirectResponse
    {
        $data = $request->validate([
            'make_id' => ['required', 'exists:vehicle_makes,id'],
            'model_id' => ['nullable', 'exists:vehicle_models,id'],
            'variant_id' => ['nullable', 'exists:vehicle_variants,id'],
            'year_from' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'year_to' => ['nullable', 'integer', 'min:1950', 'max:2100', 'gte:year_from'],
            'engine_code' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $part->fitments()->create($data + ['source' => 'manual', 'confirmed' => true]);

        return back()->with('success', 'Fitment added.');
    }

    public function destroyFitment(Part $part, PartFitment $fitment): RedirectResponse
    {
        abort_unless($fitment->part_id === $part->id, 404);
        $fitment->delete();

        return back()->with('success', 'Fitment removed.');
    }

    public function storeSupersession(Request $request, Part $part): RedirectResponse
    {
        $data = $request->validate([
            'new_part_number' => ['required', 'string', 'exists:parts,part_number'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $newPart = Part::where('part_number', $data['new_part_number'])->firstOrFail();

        if ($newPart->id === $part->id) {
            return back()->withErrors(['new_part_number' => 'A part cannot supersede itself.']);
        }

        PartSupersession::updateOrCreate(
            ['old_part_id' => $part->id, 'new_part_id' => $newPart->id],
            ['reason' => $data['reason'], 'is_active' => true, 'effective_date' => now()]
        );

        $part->update(['is_discontinued' => true, 'is_active' => false]);

        return back()->with('success', "{$part->part_number} is now superseded by {$newPart->part_number}.");
    }
}
