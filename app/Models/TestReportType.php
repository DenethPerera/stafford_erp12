<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestReportType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'category',
        'default_validity_days',
        'standard_citation',
        'description',
        'is_active',
    ];

    protected $casts = [
        'default_validity_days' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Scope active test report types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
