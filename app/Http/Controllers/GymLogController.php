<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexGymLogRequest;
use App\Http\Requests\ToggleGymLogRequest;
use App\Models\GymLog;
use Illuminate\Support\Facades\Auth;

class GymLogController
{
    public function index(IndexGymLogRequest $request)
    {
        $validated = $request->validated();
        $year = $validated['year'] ?? now()->year;
        $month = $validated['month'] ?? now()->month;

        $dates = GymLog::forUser(Auth::id())
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->pluck('date')
            ->map(fn ($date) => $date->format('Y-m-d'))
            ->all();

        return response()->json($dates);
    }

    public function toggle(ToggleGymLogRequest $request)
    {
        $date = $request->validated()['date'];

        $gymLog = GymLog::forUser(Auth::id())->where('date', $date)->first();

        if ($gymLog) {
            $gymLog->delete();

            return response()->json(['status' => 'removed']);
        }

        GymLog::create(['user_id' => Auth::id(), 'date' => $date]);

        return response()->json(['status' => 'added']);
    }

    public function stats()
    {
        $userId = Auth::id();

        return response()->json([
            'total_workouts' => GymLog::forUser($userId)->count(),
            'workouts_this_month' => GymLog::forUser($userId)->thisMonth()->count(),
        ]);
    }
}
