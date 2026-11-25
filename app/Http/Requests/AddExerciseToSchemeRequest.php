<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddExerciseToSchemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['exercise_id' => 'required|exists:exercises,id'];
    }
}
