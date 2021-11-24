<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use App\Models\ChallengeUserAttempt;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $per_page = !is_null($request->per_page) ? (int) $request->per_page : 200;
        $showByStatus = $request->show_status ?: 'all'; // all | solo | duo | team | corporate
        $order_by = $request->order_by ?: 'user_id'; // column name
        $sort_by = $request->sort_by ?: 'desc'; // asc | desc

        $users = User::with(['team.corporate', 'raceBestTimeAttempts' => function ($query) {
            $query->whereHas('challenge', function ($q) {
                $q->where('type', 'race');
            });
        }])->where('privilege', '!=', 'moderator');

        // TODO: include gps flag

        if (isset($request->search)) {
            $users->where(function ($query) use ($request) {
                $query->where('first_name', 'like', "%" . $request->search . "%")
                    ->orWhere('last_name', 'like', "%" . $request->search . "%")
                    ->orWhere('email', 'like', "%" . $request->search . "%");
            });
        }

        if ($showByStatus == 'solo') {
            $users->whereNull('team_id')->orWhere('team_id', 0);
        }

        if ($showByStatus == 'duo') {
            $users->whereNotNull('team_id')->whereHas('team', function ($q) {
                $q->where('team_type', 'duo');
            });
        }

        if ($showByStatus == 'team') {
            $users->whereNotNull('team_id')->whereHas('team', function ($q) {
                $q->where('team_type', 'team');
            });
        }

        if ($showByStatus == 'corporate') {
            $users->whereNotNull('team_id')->whereHas('team', function ($q) {
                $q->where('team_type', 'corporate');
            });
        }

        $users->withSum('moontrekkerPoints as mp_total', 'amount')
            ->withCount([
                'challengeAttempts',
                'raceAttempt',
            ])
            ->orderBy($order_by, $sort_by);

        return $users->paginate($per_page);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if ($request->user_id) {
            return $this->update($request, $request->user_id);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::with(['raceBestTimeAttempts' => function ($query) {
            $query->whereHas('challenge', function ($q) {
                $q->where('type', 'race');
            });
        }])
            ->withSum('moontrekkerPoints as mp_total', 'amount')
            ->withCount([
                'challengeAttempts',
                'raceAttempt',
            ])
            ->where('user_id', $id)->first();


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

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $fieldsToUpdate = $request->only([
            'first_name',
            'last_name',
            'email',
        ]);

        $user = User::where('user_id', $id)->first();

        if (!is_null($user)) {
            $user->update($fieldsToUpdate);

            if ($request->team_id) {
                $team = Team::whereNull('deleted_at')->find($request->team_id);

                if ($team) {
                    $user->update(['team_id' => $request->team_id]);
                }
            }

            return ["data" => $user];
        } else {
            return ["error" => ["user" => "User not found."]];
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::where('user_id', $id)->first();
        if (!is_null($user)) {
            // get all user's tokens
            $userTokens = $user->tokens;
            foreach ($userTokens as $token) {
                // revoke each token
                $token->revoke();
            }

            // update user's password to NULL
            $user->update([
                // 'password' => NULL,
                'privilege' => 'suspended',
            ]);

            return response(null, 204);
        }
    }

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

    public function getAllAttemptByUserId(Request $request, $id)
    {
        $per_page = !is_null($request->per_page) ? (int) $request->per_page : 20;

        $attempt = ChallengeUserAttempt::where('user_id', $id)->orderBy('attempt_id', 'desc')
            ->with(['progress', 'challenge.trails' => function ($query) {
                $query->select('trail_id', 'challenge_id', 'trail_index', 'longitude', 'latitude');
            }]);

        return $attempt->paginate($per_page);
    }
}
