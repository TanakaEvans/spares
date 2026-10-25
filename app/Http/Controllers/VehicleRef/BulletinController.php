<?php

namespace App\Http\Controllers\VehicleRef;

use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Controller;
use App\Models\TechnicalBulletin;
use App\Models\VehicleMake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BulletinController extends Controller
{
    use ListQuery;
    public function index(Request $request): Response
    {
        return Inertia::render('VehicleRef/Bulletins/Index', [
            'bulletins' => $this->listApply(TechnicalBulletin::with('make:id,name')
                ->when($request->string('category')->toString(), fn ($q, $c) => $q->where('category', $c)),
                $request,
                [
                    'searchable' => ['title', 'body', 'category', 'make.name'],
                    'sortable' => ['title' => 'title', 'category' => 'category', 'created_at' => 'created_at'],
                    'dateColumn' => 'created_at',
                    'defaultSort' => ['id' => 'desc'],
                ])
                ->paginate(20)->withQueryString()
                ->through(fn (TechnicalBulletin $b) => ['id' => $b->id, 'title' => $b->title, 'make_id' => $b->make_id, 'make' => $b->make?->name, 'category' => $b->category, 'body' => $b->body, 'is_active' => $b->is_active, 'at' => $b->created_at->toDateString()]),
            'makes' => VehicleMake::orderBy('name')->get(['id', 'name']),
            'filters' => $this->listFilters($request, ['category']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:200'], 'make_id' => ['nullable', 'exists:vehicle_makes,id'], 'category' => ['required', 'in:fitment_warning,service_note,recall'], 'body' => ['required', 'string', 'max:5000']]);
        TechnicalBulletin::create($data + ['is_active' => true]);

        return back()->with('success', 'Bulletin published.');
    }
}
