<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => $this->isMethod('POST') && ! $this->route('exercise')
                ? 'required|string|max:255'
                : 'sometimes|string|max:255',
            'current_weight' => $this->isMethod('POST') && ! $this->route('exercise')
                ? 'nullable|numeric|min:0'
                : 'sometimes|numeric|min:0',
        ];
    }
}
