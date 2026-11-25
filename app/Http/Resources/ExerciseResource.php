<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExerciseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'current_weight' => $this->current_weight,
            'order' => $this->whenPivotLoaded('exercise_training_scheme', fn () => $this->pivot->order),
        ];
    }
}
