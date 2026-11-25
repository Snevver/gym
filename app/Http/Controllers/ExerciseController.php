<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExerciseRequest;
use App\Http\Requests\UpdateExerciseWeightRequest;
use App\Http\Resources\ExerciseResource;
use App\Models\Exercise;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class ExerciseController
{
    use AuthorizesRequests;

    public function index()
    {
        return ExerciseResource::collection(
            Exercise::forUser(Auth::id())->orderBy('name')->get()
        );
    }

    public function store(ExerciseRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();
        $data['current_weight'] = $data['current_weight'] ?? 0;

        return new ExerciseResource(Exercise::create($data));
    }

    public function update(ExerciseRequest $request, Exercise $exercise)
    {
        $this->authorize('update', $exercise);

        $exercise->update($request->validated());

        return new ExerciseResource($exercise);
    }

    public function destroy(Exercise $exercise)
    {
        $this->authorize('delete', $exercise);

        $exercise->delete();

        return response()->json(['message' => 'Exercise deleted']);
    }

    public function updateWeight(UpdateExerciseWeightRequest $request, Exercise $exercise)
    {
        $this->authorize('update', $exercise);

        $exercise->update($request->validated());

        return new ExerciseResource($exercise);
    }
}
