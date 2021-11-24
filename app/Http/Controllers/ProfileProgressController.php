<?php

namespace App\Http\Controllers;

use App\Models\ChallengeUserAttempt;
use App\Models\User;
use Illuminate\Http\Request;

class ProfileProgressController extends Controller
{
    public function sortTotalTimeNullLast($a, $b)
    {
        if (!$a->total_time) {
            return !$b->total_time ? 0 : 1;
        }
        if (!$b->total_time) {
            return -1;
        }
        if ($a->total_time == $b->total_time) {
            return 0;
        }

        return $a->total_time < $b->total_time ? -1 : 1;
    }

    public function userProfileProgress($id)
    {
        $user = auth()->user();
        $user_id = $id;

        if ($user->privilege == "moderator") {
            $user_id = $user->user_id != $id ? $id : $user->user_id;
        }

        $user = User::with(['raceBestTimeAttempts' => function ($query) {
            $query->whereHas('challenge', function ($q) {
                $q->where('type', 'race');
            });
        }])
            ->withSum('moontrekkerPoints as mp_total', 'amount')
            ->where('user_id', $user_id)->first();


        if (!is_null($user)) {
            if (!is_null($user->team)) {
                $user->team->corporate;
                $completed = true;
                $temp = collect($user->team->participants)->map(function ($item) use (&$completed) {
                    if (is_null($item->raceAttempt)) {
                        $completed = false;
                    }
                    $item->total_time = !is_null($item->raceAttempt) ? $item->raceAttempt->total_time : NULL;
                    unset($item->raceAttempt);
                    return $item;
                })
                    ->sort(function ($a, $b) {
                        return $this->sortTotalTimeNullLast($a, $b);
                    })->values();
                unset($user->team->participants);
                $user->team->participants = $temp;
                $user->team->team_race_best_time = $completed ? $temp->avg('total_time') : 0;
            }
            return $user;
        }

        abort(400);
    }

    public function userProfileChallengeProgress(Request $request, $id, $type)
    {
        $user = auth()->user();
        $user_id = $id;
        $per_page = !is_null($request->per_page) ? (int) $request->per_page : 20;
        $filter = [];
        switch ($type) {
            case 'race':
                $filter = ['race'];
                break;
            case 'training':
                $filter = ['training'];
                break;
            case 'challenge':
                $filter = ['challenge_single', 'challenge_standard', 'challenge_either_end', 'challenge_training'];
                break;
            default:
                abort(400);
        }

        if ($user->privilege == "moderator") {
            $user_id = $user->user_id != $id ? $id : $user->user_id;
        }

        $challengeAttempts = ChallengeUserAttempt::with(['challenge' => function ($query) {
            $query->withCount([
                'countRewardRedeemed as total_reward_claimed'
            ]);
        }])
            ->whereHas('challenge', function ($q) use ($filter) {
                $q->whereIn('type', $filter);
            })->where('user_id', $user_id);


        $challengeAttempts->orderBy('ended_at', 'desc');

        return $challengeAttempts->paginate($per_page);
    }
}
