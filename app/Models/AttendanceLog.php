<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $table = 'logs';

    protected $fillable = [
        'id_num',
        'date_today',
        'time_in',
        'time_out',
        'break1_out',
        'break1_in',
        'break2_out',
        'break2_in',
        'break3_out',
        'break3_in',
        'status',
        'overtime_minutes',
    ];

    protected $casts = [
        'date_today'  => 'date',
        'time_in'     => 'datetime',
        'time_out'    => 'datetime',
        'break1_out'  => 'datetime',
        'break1_in'   => 'datetime',
        'break2_out'  => 'datetime',
        'break2_in'   => 'datetime',
        'break3_out'  => 'datetime',
        'break3_in'   => 'datetime',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'id_num', 'id_num');
    }

    // Computed: total worked hours (excluding breaks)
    public function getWorkedHoursAttribute(): ?float
    {
        if (!$this->time_in || !$this->time_out) return null;

        $totalMinutes = $this->time_in->diffInMinutes($this->time_out);

        // Subtract completed breaks
        foreach ([['break1_out','break1_in'],['break2_out','break2_in'],['break3_out','break3_in']] as [$out, $in]) {
            if ($this->{$out} && $this->{$in}) {
                $totalMinutes -= $this->{$out}->diffInMinutes($this->{$in});
            }
        }

        return round($totalMinutes / 60, 2);
    }

    // Determine what the next punch action should be
    public function getNextActionAttribute(): string
    {
        if (!$this->time_in)     return 'time_in';
        if (!$this->break1_out)  return 'break1_out';
        if (!$this->break1_in)   return 'break1_in';
        if (!$this->break2_out)  return 'break2_out';
        if (!$this->break2_in)   return 'break2_in';
        if (!$this->break3_out)  return 'break3_out';
        if (!$this->break3_in)   return 'break3_in';
        if (!$this->time_out)    return 'time_out';
        return 'complete';
    }
}