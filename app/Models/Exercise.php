<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Exercise extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'current_weight',
    ];

    protected $casts = [
        'current_weight' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trainingSchemes(): BelongsToMany
    {
        return $this->belongsToMany(TrainingScheme::class)
            ->withTimestamps()
            ->withPivot('order')
            ->orderByPivot('order');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
