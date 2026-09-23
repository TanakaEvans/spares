<?php
namespace App\Http\Controllers\VehicleRef;
use App\Http\Controllers\Controller;
use App\Models\TechnicalBulletin;
use App\Models\VehicleMake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
class BulletinController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('VehicleRef/Bulletins/Index', [
            'bulletins' => TechnicalBulletin::with('make:id,name')->latest('id')->paginate(20)
                ->through(fn(TechnicalBulletin $b) => ['id' => $b->id, 'title' => $b->title, 'make' => $b->make?->name, 'category' => $b->category, 'body' => $b->body, 'is_active' => $b->is_active, 'at' => $b->created_at->toDateString()]),
            'makes' => VehicleMake::orderBy('name')->get(['id', 'name']),
        ]);
    }
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:200'], 'make_id' => ['nullable', 'exists:vehicle_makes,id'], 'category' => ['required', 'in:fitment_warning,service_note,recall'], 'body' => ['required', 'string', 'max:5000']]);
        TechnicalBulletin::create($data + ['is_active' => true]);
        return back()->with('success', 'Bulletin published.');
    }
}
