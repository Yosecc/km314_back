<?php

namespace App\Services;

use App\Models\ActivitiesPeople;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CurrentPeopleInsideQuery
{
    /** Personas cuyo último movimiento registrado es una entrada. */
    public static function make(): Builder
    {
        $rankedMovements = DB::table('activities_people as ranked_people')
            ->join('activities as ranked_activity', 'ranked_activity.id', '=', 'ranked_people.activities_id')
            ->whereNull('ranked_people.deleted_at')
            ->selectRaw('ranked_people.id, ROW_NUMBER() OVER (PARTITION BY ranked_people.model, ranked_people.model_id ORDER BY ranked_activity.created_at DESC, ranked_people.id DESC) AS movement_rank');

        $latestIds = DB::query()
            ->fromSub($rankedMovements, 'latest_movements')
            ->where('movement_rank', 1)
            ->select('id');

        return ActivitiesPeople::query()
            ->select('activities_people.*')
            ->join('activities as current_activity', 'current_activity.id', '=', 'activities_people.activities_id')
            ->whereIn('activities_people.id', $latestIds)
            ->where('current_activity.type', 'Entry')
            ->orderByDesc('current_activity.created_at')
            ->orderByDesc('activities_people.id');
    }
}
