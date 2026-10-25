<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerNoteController extends Controller
{
    use ListQuery;
    public function index(Request $request): Response
    {
        return Inertia::render('Customers/Comms/Index', [
            'notes' => $this->listApply(
                CustomerNote::with('customer:id,name', 'user:id,name')
                    ->when($request->string('channel')->toString(), fn ($q, $c) => $q->where('channel', $c)),
                $request,
                [
                    'searchable' => ['subject', 'body', 'customer.name'],
                    'sortable' => ['channel' => 'channel', 'created_at' => 'created_at'],
                    'dateColumn' => 'created_at',
                    'defaultSort' => ['id' => 'desc'],
                ]
            )->paginate(25)->withQueryString()
                ->through(fn (CustomerNote $n) => ['id' => $n->id, 'customer' => $n->customer?->name, 'customer_id' => $n->customer_id,
                    'channel' => $n->channel, 'subject' => $n->subject, 'body' => $n->body, 'user' => $n->user?->name, 'user_id' => $n->user_id, 'at' => $n->created_at->toDateTimeString()]),
            'customers' => Customer::where('is_walk_in', false)->orderBy('name')->limit(200)->get(['id', 'name']),
            'filters' => $this->listFilters($request, ['channel']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['customer_id' => ['required', 'exists:customers,id'], 'channel' => ['required', 'in:call,email,visit,note'], 'subject' => ['required', 'string', 'max:200'], 'body' => ['nullable', 'string', 'max:2000']]);
        CustomerNote::create($data + ['user_id' => $request->user()->id]);

        return back()->with('success', 'Note logged.');
    }
}
