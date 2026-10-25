<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * The shared list-query "trait": one call applies the toolbar's contract —
 * `search` across a whitelist of columns (or `relation.column`), `sort`+`dir`
 * across a whitelist of sort keys, and `date_from`/`date_to` on one column.
 * Everything is opt-in per screen so no column is ever searched/sorted on
 * unless the page advertises it.
 */
trait ListQuery
{
    /**
     * @param  array{
     *     searchable?: array<int,string>,
     *     sortable?: array<string,string>,
     *     defaultSort?: array<string,string>,
     *     dateColumn?: string,
     * }  $config
     */
    protected function listApply(Builder $query, Request $request, array $config): Builder
    {
        $search = trim((string) $request->query('search', ''));

        if ($search !== '' && ! empty($config['searchable'])) {
            $query->where(function ($q) use ($search, $config) {
                foreach ($config['searchable'] as $col) {
                    if (str_contains($col, '.')) {
                        [$rel, $c] = explode('.', $col, 2);
                        $q->orWhereHas($rel, fn ($r) => $r->where($c, 'like', "%{$search}%"));
                    } else {
                        $q->orWhere($col, 'like', "%{$search}%");
                    }
                }
            });
        }

        if (! empty($config['dateColumn'])) {
            if ($from = $request->query('date_from')) {
                $query->whereDate($config['dateColumn'], '>=', $from);
            }
            if ($to = $request->query('date_to')) {
                $query->whereDate($config['dateColumn'], '<=', $to);
            }
        }

        $sortKey = (string) $request->query('sort', '');
        $dir = strtolower((string) $request->query('dir')) === 'asc' ? 'asc' : 'desc';

        if ($sortKey !== '' && isset($config['sortable'][$sortKey])) {
            $query->orderBy($config['sortable'][$sortKey], $dir);
        } else {
            foreach ($config['defaultSort'] ?? ['id' => 'desc'] as $col => $d) {
                $query->orderBy($col, $d);
            }
        }

        return $query;
    }

    /**
     * The toolbar's filter state to echo back to the page — merge the keys the
     * screen itself owns (status pills, type, on_hold…) on top.
     */
    protected function listFilters(Request $request, array $screenKeys = []): array
    {
        return $request->only(array_merge(
            ['search', 'sort', 'dir', 'date_from', 'date_to'],
            $screenKeys
        ));
    }
}
