<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MentalStateType extends Model
{
    protected $fillable = [
        'key',
        'label',
        'color',
        'severity',
        'order',
        'capacity_impact',
        'is_breakdown',
    ];

    protected $casts = [
        'severity' => 'integer',
        'order' => 'integer',
        'capacity_impact' => 'integer',
        'is_breakdown' => 'boolean',
    ];
}
