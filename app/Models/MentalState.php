<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentalState extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'state_key',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stateType(): BelongsTo
    {
        return $this->belongsTo(MentalStateType::class, 'state_key', 'key');
    }
}
