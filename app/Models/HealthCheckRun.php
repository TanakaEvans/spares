<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One persisted System Health run (scheduled or manual) — operations/system-health.md §Behaviour 2. */
class HealthCheckRun extends Model
{
    protected $fillable = ['trigger', 'overall', 'ok_count', 'warn_count', 'fail_count', 'results', 'ran_at'];

    protected $casts = ['results' => 'array', 'ran_at' => 'datetime'];
}
