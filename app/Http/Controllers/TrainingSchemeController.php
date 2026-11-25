<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddExerciseToSchemeRequest;
use App\Http\Requests\ReorderTrainingSchemesRequest;
use App\Http\Requests\TrainingSchemeRequest;
use App\Http\Resources\TrainingSchemeResource;
use App\Models\Exercise;
use App\Models\TrainingScheme;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TrainingSchemeController
{
    use AuthorizesRequests;

    public function index()
    {
        $schemes = TrainingScheme::forUser(Auth::id())
            ->with('exercises')
            ->orderBy('order')
            ->orderByDesc('created_at')
            ->get();

        return TrainingSchemeResource::collection($schemes);
    }

    public function store(TrainingSchemeRequest $request)
    {
        $scheme = TrainingScheme::create($request->validated() + ['user_id' => Auth::id()]);

        return new TrainingSchemeResource($scheme->load('exercises'));
    }

    public function update(TrainingSchemeRequest $request, TrainingScheme $scheme)
    {
        $this->authorize('update', $scheme);

        $scheme->update($request->validated());

        return new TrainingSchemeResource($scheme->load('exercises'));
    }

    public function destroy(TrainingScheme $scheme)
    {
        $this->authorize('delete', $scheme);

        $scheme->delete();

        return response()->json(['message' => 'Scheme deleted']);
    }

    public function reorder(ReorderTrainingSchemesRequest $request)
    {
        $data = $request->validated();

        $ownedIds = TrainingScheme::whereIn('id', $data['ordered_ids'])
            ->where('user_id', Auth::id())
            ->pluck('id')
            ->all();

        $orderMap = [];
        foreach ($data['ordered_ids'] as $order => $id) {
            if (in_array($id, $ownedIds, true)) {
                $orderMap[$id] = $order;
            }
        }

        if ($orderMap) {
            $cases = collect($orderMap)
                ->map(fn ($order, $id) => "WHEN {$id} THEN {$order}")
                ->implode(' ');
            $idList = implode(',', array_keys($orderMap));

            DB::update("UPDATE training_schemes SET `order` = CASE id {$cases} END WHERE id IN ({$idList})");
        }

        return response()->json(['message' => 'Reordered']);
    }

    public function addExercise(AddExerciseToSchemeRequest $request, TrainingScheme $scheme)
    {
        $this->authorize('update', $scheme);

        $exercise = Exercise::findOrFail($request->validated()['exercise_id']);
        $this->authorize('update', $exercise);

        $maxOrder = $scheme->exercises()->max('order') ?? -1;
        $scheme->exercises()->attach($exercise->id, ['order' => $maxOrder + 1]);

        return new TrainingSchemeResource($scheme->load('exercises'));
    }

    public function removeExercise(TrainingScheme $scheme, Exercise $exercise)
    {
        $this->authorize('update', $scheme);

        $scheme->exercises()->detach($exercise->id);

        return new TrainingSchemeResource($scheme->load('exercises'));
    }
}
