<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

/**
 * Master-data delete policy (component-standards §5b): a record nothing
 * refers to is deleted; a record still referenced is deactivated instead
 * (is_active = false) so documents, ledgers and history never point at a
 * hole. Records without an is_active flag that are in use are refused.
 */
trait RetiresRecords
{
    /**
     * @param  array<string,int>  $usage  e.g. ['part' => 12, 'model' => 3] — counts of things still referring to it
     * @param  string|null  $afterDelete  route to go to after a real delete (detail pages can't go "back")
     */
    protected function retireOrDelete(Model $record, string $label, array $usage, ?string $afterDelete = null): RedirectResponse
    {
        $used = array_filter($usage, fn ($n) => $n > 0);

        if ($used !== []) {
            $what = collect($used)->map(fn ($n, $noun) => $n.' '.$noun.($n === 1 ? '' : 's'))->join(', ', ' and ');

            if (! array_key_exists('is_active', $record->getAttributes())) {
                return back()->with('error', "{$label} is still used by {$what} — it can't be deleted.");
            }

            $record->update(['is_active' => false]);

            return back()->with('success', "{$label} is still used by {$what}, so it was deactivated instead of deleted. Reactivate it any time from Edit.");
        }

        $record->delete();

        return ($afterDelete ? redirect()->route($afterDelete) : back())->with('success', "{$label} deleted.");
    }
}
