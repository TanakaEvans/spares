# Coding Standards: PHP / Laravel

> Standards for all backend code in SparesPro. Laravel 12, PHP 8.2+, nwidart/laravel-modules architecture.

---

## Module Structure (nwidart)

Every feature lives in its own module under `Modules/`. No business logic in the base `app/` directory — that is reserved for shared infrastructure only.

```
Modules/
  InventoryManagement/
    app/
      Http/
        Controllers/
          PartController.php
          StockController.php
          StockTakeController.php
        Requests/
          Parts/
            StorePartRequest.php
            UpdatePartRequest.php
          StockTakes/
            CreateStockTakeRequest.php
        Resources/
          PartResource.php
          StockLevelResource.php
      Models/
        Part.php
        StockLevel.php
        StockLedger.php
        BinLocation.php
      Policies/
        PartPolicy.php
        StockTakePolicy.php
      Services/
        StockLedgerService.php
        StockTakeService.php
        ReorderService.php
      Events/
        StockAdjusted.php
        StockLevelBelowReorderPoint.php
      Listeners/
        SendLowStockAlert.php
        UpdateStockCache.php
    config/
      config.php
    database/
      migrations/
        2026_01_01_000001_create_parts_table.php
        2026_01_01_000002_create_part_categories_table.php
      seeders/
        PartCategorySeeder.php
    resources/
      js/
        Pages/
          Parts/
            Index.jsx
            Create.jsx
            Edit.jsx
            Show.jsx
        Components/
          PartSearch.jsx
    routes/
      web.php
    module.json
```

### Base `app/` — Shared Infrastructure Only
```
app/
  Http/
    Controllers/
      Controller.php         (base class)
    Middleware/
      HandleInertiaRequests.php
      EnsureHasRole.php
  Models/
    User.php
    Company.php
    Branch.php
    SystemModule.php
    SystemRoute.php
  Services/
    (shared services only)
```

---

## Naming Conventions

### Files & Classes
| Type | Convention | Example |
|------|-----------|---------|
| Controller | PascalCase + `Controller` | `PartController` |
| Model | PascalCase, singular | `Part`, `StockLevel` |
| Form Request | `Store`\|`Update` + Model + `Request` | `StorePartRequest` |
| API Resource | Model + `Resource` | `PartResource` |
| Resource Collection | Model + `Collection` | `PartCollection` |
| Service | Noun + `Service` | `StockLedgerService` |
| Event | Past tense, PascalCase | `StockAdjusted`, `PartCreated` |
| Listener | Verb phrase, PascalCase | `SendLowStockAlert` |
| Job | Verb phrase | `ProcessGrnReceiving` |
| Policy | Model + `Policy` | `PartPolicy` |
| Seeder | Model + `Seeder` | `PartCategorySeeder` |
| Migration | `YYYY_MM_DD_HHMMSS_verb_table` | `2026_01_01_000001_create_parts_table` |

### Database (see also [database.md](database.md))
| Type | Convention | Example |
|------|-----------|---------|
| Tables | snake_case, plural | `parts`, `stock_levels` |
| Columns | snake_case | `part_number`, `is_active` |
| Foreign keys | `{relation}_id` | `customer_id`, `part_id` |
| Pivot tables | Alphabetical, both nouns | `part_price_list` |
| Booleans | `is_` prefix | `is_active`, `is_oem` |
| Timestamps | Standard `created_at`, `updated_at`, `deleted_at` |

---

## Controller Standards

### Resource Controller Pattern
Always follow Laravel's resource controller naming. Do not invent custom method names when a standard one fits.

```php
class PartController extends Controller
{
    public function index(Request $request): Response
    public function create(): Response
    public function store(StorePartRequest $request): RedirectResponse
    public function show(Part $part): Response
    public function edit(Part $part): Response
    public function update(UpdatePartRequest $request, Part $part): RedirectResponse
    public function destroy(Part $part): RedirectResponse
}
```

### Rules
1. **No business logic in controllers.** Controllers handle HTTP: receive input, call a service, return a response.
2. **Always use Form Requests** for validation — not inline `$request->validate()`.
3. **Authorize via Policies**, not inline `if` checks.
4. **Return Inertia responses** for full-page renders; JSON for API endpoints.
5. Use **route model binding** — accept `Part $part` not `int $id`.

```php
// ✅ Good
public function store(StorePartRequest $request): RedirectResponse
{
    $this->authorize('create', Part::class);
    $part = $this->partService->create($request->validated());
    return redirect()->route('inventory.parts.show', $part)
        ->with('success', 'Part created successfully.');
}

// ❌ Bad — validation inline, no policy, business logic in controller
public function store(Request $request)
{
    $request->validate(['part_number' => 'required']);
    $part = new Part($request->all());
    $part->save();
    // ... more logic
}
```

### Inertia Responses
```php
use Inertia\Inertia;

public function index(Request $request): Response
{
    return Inertia::render('Parts/Index', [
        'parts' => PartResource::collection(
            Part::with('category', 'brand')
                ->filter($request->only('search', 'category_id', 'status'))
                ->paginate(25)
        ),
        'categories' => PartCategory::active()->get(['id', 'name']),
    ]);
}
```

---

## Model Standards

```php
class Part extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'part_number', 'oem_number', 'description', 'short_description',
        'category_id', 'brand_id', 'unit_of_measure_id', 'barcode_ean',
        'weight_kg', 'is_oem', 'has_serial_tracking', 'is_active', 'notes',
    ];

    protected $casts = [
        'is_oem'               => 'boolean',
        'has_serial_tracking'  => 'boolean',
        'is_active'            => 'boolean',
        'weight_kg'            => 'decimal:3',
    ];

    // Relationships — always type-hinted
    public function category(): BelongsTo
    {
        return $this->belongsTo(PartCategory::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(PartBrand::class);
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function fitments(): HasMany
    {
        return $this->hasMany(PartFitment::class);
    }

    // Scopes — prefix with 'scope'
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, fn($q, $s) =>
                $q->where('description', 'like', "%{$s}%")
                  ->orWhere('part_number', 'like', "%{$s}%")
                  ->orWhere('oem_number', 'like', "%{$s}%")
            )
            ->when($filters['category_id'] ?? null, fn($q, $id) =>
                $q->where('category_id', $id)
            );
    }
}
```

### Rules
1. Always use `$fillable` (never `$guarded = []`).
2. Cast all booleans, decimals, dates, and JSON columns in `$casts`.
3. Use `SoftDeletes` for all business entities (parts, customers, suppliers, etc.).
4. No business logic in models — only relationships, scopes, and accessors/mutators.
5. Relationships must be explicitly defined with return types.

---

## Service Layer

All write operations with business logic go through dedicated service classes.

```php
class StockLedgerService
{
    public function post(
        Part $part,
        Branch $branch,
        string $transactionType,
        float $qty,
        float $unitCost,
        string $referenceType,
        int $referenceId,
        ?string $notes = null,
    ): StockLedger {
        return DB::transaction(function () use ($part, $branch, $transactionType, $qty, $unitCost, $referenceType, $referenceId, $notes) {
            $stockLevel = StockLevel::lockForUpdate()
                ->where('part_id', $part->id)
                ->where('branch_id', $branch->id)
                ->firstOrFail();

            $runningBalance = $stockLevel->qty_on_hand + $qty;

            $entry = StockLedger::create([
                'part_id'          => $part->id,
                'branch_id'        => $branch->id,
                'transaction_type' => $transactionType,
                'qty'              => $qty,
                'unit_cost'        => $unitCost,
                'running_balance'  => $runningBalance,
                'reference_type'   => $referenceType,
                'reference_id'     => $referenceId,
                'notes'            => $notes,
                'user_id'          => auth()->id(),
            ]);

            $stockLevel->update(['qty_on_hand' => $runningBalance]);

            return $entry;
        });
    }
}
```

### Rules
1. Wrap multi-step operations in `DB::transaction()`.
2. Use `lockForUpdate()` when reading stock levels before updating — prevents race conditions.
3. Services throw domain-specific exceptions (e.g., `InsufficientStockException`).
4. Services are injected via constructor (for testability).

---

## Form Requests

```php
class StorePartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Part::class);
    }

    public function rules(): array
    {
        return [
            'part_number'       => ['required', 'string', 'max:50', 'unique:parts,part_number'],
            'description'       => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:100'],
            'category_id'       => ['required', 'exists:part_categories,id'],
            'brand_id'          => ['required', 'exists:part_brands,id'],
            'is_oem'            => ['required', 'boolean'],
            'weight_kg'         => ['nullable', 'numeric', 'min:0', 'max:9999'],
        ];
    }

    public function messages(): array
    {
        return [
            'part_number.unique' => 'This part number already exists in the system.',
        ];
    }
}
```

---

## API Resources

Transform models for the frontend. Never expose model attributes directly to Inertia — always use a Resource.

```php
class PartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'part_number'       => $this->part_number,
            'oem_number'        => $this->oem_number,
            'description'       => $this->description,
            'short_description' => $this->short_description,
            'is_oem'            => $this->is_oem,
            'is_active'         => $this->is_active,
            'category'          => [
                'id'   => $this->category?->id,
                'name' => $this->category?->name,
            ],
            'brand'             => [
                'id'   => $this->brand?->id,
                'name' => $this->brand?->name,
            ],
            'stock'             => $this->whenLoaded('stockLevels', fn() =>
                $this->stockLevels->map(fn($s) => [
                    'branch_id'    => $s->branch_id,
                    'qty_on_hand'  => $s->qty_on_hand,
                    'qty_reserved' => $s->qty_reserved,
                    'qty_available'=> $s->qty_on_hand - $s->qty_reserved,
                ])
            ),
            'created_at'        => $this->created_at->toDateString(),
        ];
    }
}
```

---

## Policies

```php
class PartPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('inventory.parts.index');
    }

    public function view(User $user, Part $part): bool
    {
        return $user->hasPermission('inventory.parts.show');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('inventory.parts.create');
    }

    public function update(User $user, Part $part): bool
    {
        return $user->hasPermission('inventory.parts.edit');
    }

    public function delete(User $user, Part $part): bool
    {
        return $user->hasPermission('inventory.parts.destroy');
    }
}
```

---

## Events & Listeners

Use events for decoupled side-effects (notifications, cache invalidation, analytics).

```php
// Event
class StockLevelBelowReorderPoint
{
    public function __construct(
        public readonly Part $part,
        public readonly Branch $branch,
        public readonly float $currentLevel,
    ) {}
}

// Listener
class SendLowStockAlert implements ShouldQueue
{
    public function handle(StockLevelBelowReorderPoint $event): void
    {
        $buyers = User::role('buyer')->get();
        Notification::send($buyers, new LowStockNotification($event->part, $event->branch));
    }
}
```

---

## Database Query Optimisation

```php
// ✅ Eager load relationships to avoid N+1
Part::with(['category', 'brand', 'stockLevels'])->paginate(25);

// ✅ Select only needed columns
Part::select('id', 'part_number', 'description', 'category_id')
    ->with('category:id,name')
    ->get();

// ✅ Use when() for conditional queries
$query->when($request->search, fn($q, $s) =>
    $q->where('description', 'like', "%{$s}%")
);

// ❌ Never load all then filter in PHP
Part::all()->where('is_active', true);
```

---

## Testing Standards

### Test Structure
```
Modules/InventoryManagement/tests/
  Feature/
    PartManagementTest.php
    StockTakeTest.php
    ReorderManagementTest.php
  Unit/
    Services/
      StockLedgerServiceTest.php
```

### Test Requirements
- Every controller action has at least one Feature test.
- Every Service method has Unit tests.
- Tests use `RefreshDatabase` trait and factories.
- No real HTTP calls in tests — use `actingAs()` for auth.
- Business rule violations must be tested as well as happy paths.

```php
class StockLedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_posting_a_sale_reduces_stock_on_hand(): void
    {
        $part = Part::factory()->create();
        $branch = Branch::factory()->create();
        StockLevel::factory()->create([
            'part_id'    => $part->id,
            'branch_id'  => $branch->id,
            'qty_on_hand' => 10,
        ]);

        $service = new StockLedgerService();
        $service->post($part, $branch, 'SALE', -3, 15.00, 'SalesDocument', 1);

        $this->assertDatabaseHas('stock_levels', [
            'part_id'    => $part->id,
            'qty_on_hand' => 7,
        ]);
    }
}
```

---

## Queue & Background Jobs

Long-running operations (bulk imports, report generation, email sending) must be queued.

```php
// Dispatch a job
ProcessSupplierPriceListImport::dispatch($priceList)->onQueue('imports');

// The job
class ProcessSupplierPriceListImport implements ShouldQueue
{
    public int $tries = 3;
    public int $timeout = 300;

    public function handle(SupplierPriceListImportService $service): void
    {
        $service->process($this->priceList);
    }
}
```

Use `database` queue driver (no Redis needed initially). Switch to `redis` if throughput demands it.
