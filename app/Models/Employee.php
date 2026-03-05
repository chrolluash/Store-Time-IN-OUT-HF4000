<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_num',
        'full_name',
        'position',
        'store_dept',
        'shift_from',
        'shift_to',
        'fingerprint_1',
        'fingerprint_2',
    ];

    protected $hidden = [
        'fingerprint_1',
        'fingerprint_2',
    ];

    protected $casts = [
        'shift_from' => 'string',
        'shift_to'   => 'string',
    ];

    // Relationships
    public function logs()
    {
        return $this->hasMany(AttendanceLog::class, 'id_num', 'id_num');
    }

    public function todayLog()
    {
        return $this->hasOne(AttendanceLog::class, 'id_num', 'id_num')
                    ->whereDate('date_today', today());
    }

    // Helpers
    public function hasFingerprints(): bool
    {
        return !empty($this->fingerprint_1) && !empty($this->fingerprint_2);
    }

    public function getEnrollmentStatusAttribute(): string
    {
        if ($this->fingerprint_1 && $this->fingerprint_2) return 'complete';
        if ($this->fingerprint_1 || $this->fingerprint_2) return 'partial';
        return 'none';
    }
}