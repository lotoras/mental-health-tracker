<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentalCapacityLog extends Model
{
    protected $fillable = [
        'user_id',
        'mental_state_id',
        'date',
        'capacity_before',
        'capacity_after',
        'capacity_change',
        'triggered_breakdown',
    ];

    protected $casts = [
        'date' => 'date',
        'capacity_before' => 'integer',
        'capacity_after' => 'integer',
        'capacity_change' => 'integer',
        'triggered_breakdown' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mentalState(): BelongsTo
    {
        return $this->belongsTo(MentalState::class);
    }
}
