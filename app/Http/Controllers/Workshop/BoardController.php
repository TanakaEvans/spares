<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Models\JobCard;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
    /** The technician board — live jobs grouped by status column. */
    public function index(Request $request): Response
    {
        $columns = ['open', 'allocated', 'in_progress', 'awaiting_parts', 'quality_check', 'completed'];

        $jobs = JobCard::with('customer:id,name', 'vehicle:id,registration', 'technician:id,name')
            ->whereIn('status', $columns)
            ->when($request->integer('branch_id'), fn ($q, $b) => $q->where('branch_id', $b))
            ->latest('id')->get();

        $board = [];
        foreach ($columns as $col) {
            $board[$col] = $jobs->where('status', $col)->map(fn (JobCard $j) => [
                'id' => $j->id,
                'job_number' => $j->job_number,
                'customer' => $j->customer?->name,
                'vehicle' => $j->vehicle?->registration,
                'technician' => $j->technician?->name,
                'promised_at' => $j->promised_at?->toDateTimeString(),
            ])->values();
        }

        return Inertia::render('Workshop/Board/Index', [
            'columns' => $columns,
            'board' => $board,
        ]);
    }
}
