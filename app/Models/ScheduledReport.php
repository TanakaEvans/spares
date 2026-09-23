<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ScheduledReport extends Model
{
    protected $fillable = ['name', 'report_key', 'frequency', 'run_time', 'recipients', 'format', 'is_active', 'last_run_at'];
    protected $casts = ['is_active' => 'boolean', 'last_run_at' => 'datetime'];
}
