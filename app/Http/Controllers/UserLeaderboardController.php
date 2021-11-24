<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\CollectionHelper;
use App\Models\Corporate;

class UserLeaderboardController extends Controller
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

    public function sortTotalTimeZeroLast($a, $b)
    {
        if ($a->overall_total_time == 0) {
            return $b->overall_total_time == 0 ? 0 : 1;
        }
        if ($b->overall_total_time == 0) {
            return -1;
        }
        if ($a->overall_total_time == $b->overall_total_time) {
            return 0;
        }

        return $a->overall_total_time < $b->overall_total_time ? -1 : 1;
    }

    public function leaderboardMoontrekkerPoints(Request $request, $type)
    {
        $per_page = !is_null($request->per_page) ? (int) $request->per_page : 100;

        switch ($type) {
            case 'solo':
                $users = User::withSum('moontrekkerPoints as mp_total', 'amount')
                    ->whereNull("team_id")
                    ->whereIn("privilege", ["user", "moderator"])
                    ->orderBy('mp_total', 'desc')
                    ->orderBy('first_name', 'asc')
                    ->orderBy('last_name', 'desc')
                    ->paginate($per_page);
                return $users;
            case 'duo':
                $usersDuo = Team::with(['participants' => function ($query) {
                    $query->withSum('moontrekkerPoints as mp_total', 'amount')
                        ->orderBy('mp_total', 'desc')
                        ->orderBy('first_name', 'asc')
                        ->orderBy('last_name', 'desc');
                }])
                    ->where('team_type', 'duo')->get();


                $usersDuoWithOverall = collect($usersDuo)->map(function ($item) {
                    $item->mp_overall_total = collect($item->participants)->sum('mp_total');
                    return $item;
                })->sortBy([
                    ['mp_overall_total', 'desc'],
                    ['team_name', 'asc']
                ]);


                return CollectionHelper::paginate(
                    $usersDuoWithOverall,
                    $per_page
                );
            case 'team':
                $usersTeam = Team::with(['participants' => function ($query) {
                    $query->withSum('moontrekkerPoints as mp_total', 'amount')
                        ->orderBy('mp_total', 'desc')
                        ->orderBy('first_name', 'asc')
                        ->orderBy('last_name', 'desc');
                }])
                    ->where('team_type', 'team')->get();


                $usersTeamWithOverall = collect($usersTeam)->map(function ($item) {
                    $item->mp_overall_total = collect($item->participants)->sum('mp_total');
                    return $item;
                })->sortBy([
                    ['mp_overall_total', 'desc'],
                    ['team_name', 'asc']
                ]);


                return CollectionHelper::paginate(
                    $usersTeamWithOverall,
                    $per_page
                );

            case 'corporate-team':
                $usersCorporateTeam = Team::with([
                    'corporate',
                    'participants' => function ($query) {
                        $query->withSum('moontrekkerPoints as mp_total', 'amount')
                            ->orderBy('mp_total', 'desc')
                            ->orderBy('first_name', 'asc')
                            ->orderBy('last_name', 'desc');
                    }
                ])
                    ->where('team_type', 'corporate')->get();

                $usersCorpoTeamWithOverall = collect($usersCorporateTeam)->map(function ($item) {
                    $item->mp_overall_total = collect($item->participants)->sum('mp_total');
                    return $item;
                })->sortBy([
                    ['mp_overall_total', 'desc'],
                    ['team_name', 'asc']
                ]);


                return CollectionHelper::paginate(
                    $usersCorpoTeamWithOverall,
                    $per_page
                );

            case 'corporate-cup':
                $corporates = Corporate::with(['team' => function ($query) {
                    $query->with(['participants'  => function ($query) {
                        $query->withSum('moontrekkerPoints as mp_total', 'amount');
                    }]);
                }])->get();

                $corporates = $corporates->map(function ($item) {
                    $corporate_mp_overall = 0;
                    $item->team->map(function ($t) use (&$corporate_mp_overall) {
                        $corporate_mp_overall += $t->participants->sum('mp_total');
                    });
                    unset($item->team);
                    $item->mp_overall_total = $corporate_mp_overall;
                    return $item;
                })->sortBy([
                    ['mp_overall_total', 'desc'],
                    ['business_name', 'asc']
                ]);
                return CollectionHelper::paginate(
                    $corporates,
                    $per_page
                );
            default:
                abort(400);
        }
    }

    public function leaderboardRaceTimes(Request $request, $type)
    {
        $per_page = !is_null($request->per_page) ? (int) $request->per_page : 100;
        switch ($type) {
            case 'solo':
                $users = User::with(['raceAttempt' => function ($query) {
                    $query->whereHas('challenge', function ($q) {
                        $q->where('type', 'race');
                    });
                }])
                    ->whereNull("team_id")
                    ->withSum('moontrekkerPoints as mp_total', 'amount')
                    ->whereIn("privilege", ["user", "moderator"])
                    ->orderBy('first_name', 'asc')
                    ->orderBy('last_name', 'desc')
                    ->get()
                    ->map(function ($item) {
                        $item->total_time = !is_null($item->raceAttempt) ? $item->raceAttempt->total_time : NULL;
                        unset($item->raceAttempt);
                        return $item;
                    })->sort(function ($a, $b) {
                        return $this->sortTotalTimeNullLast($a, $b);
                    });

                return CollectionHelper::paginate($users, $per_page);
            case 'duo':
                $usersDuo = Team::with(['participants' => function ($query) {
                    $query
                        ->withSum('moontrekkerPoints as mp_total', 'amount')
                        ->with(['raceAttempt' => function ($query) {
                            $query->whereHas('challenge', function ($q) {
                                $q->where('type', 'race');
                            });
                        }])->orderBy('first_name', 'asc')->orderBy('last_name', 'desc');
                }])->where('team_type', 'duo')->get();


                $usersDuoWithOverall = collect($usersDuo)->map(function ($item) {
                    $userCollection = collect($item->participants);
                    $completed = true;
                    $tempSorted = $userCollection->map(function ($item) use (&$completed) {
                        if (is_null($item->raceAttempt)) {
                            $completed = false;
                        }
                        $item->total_time = !is_null($item->raceAttempt) ? $item->raceAttempt->total_time : NULL;
                        unset($item->raceAttempt);
                        return $item;
                    })->sort(function ($a, $b) {
                        return $this->sortTotalTimeNullLast($a, $b);
                    })->values();

                    unset($item->participants);
                    $item->participants = $tempSorted;
                    $tempOverAll =  $completed ? $item->participants->avg('total_time') : 0;
                    $item->overall_total_time = $tempOverAll;
                    return $item;
                })
                    ->sort(function ($a, $b) {
                        return $this->sortTotalTimeZeroLast($a, $b);
                    })
                    ->values();


                return CollectionHelper::paginate($usersDuoWithOverall, $per_page);
            case 'team':
                $usersTeam = Team::with(['participants' => function ($query) {
                    $query->withSum('moontrekkerPoints as mp_total', 'amount')
                        ->with(['raceAttempt' => function ($query) {
                            $query->whereHas('challenge', function ($q) {
                                $q->where('type', 'race');
                            });
                        }])->orderBy('first_name', 'asc')->orderBy('last_name', 'desc');
                }])->where('team_type', 'team')->get();


                $usersTeamWithOverall = collect($usersTeam)->map(function ($item) {
                    $userCollection = collect($item->participants);
                    $completed = true;
                    $tempSorted = $userCollection->map(function ($item) use (&$completed) {
                        if (is_null($item->raceAttempt)) {
                            $completed = false;
                        }
                        $item->total_time = !is_null($item->raceAttempt) ? $item->raceAttempt->total_time : NULL;
                        unset($item->raceAttempt);
                        return $item;
                    })->sort(function ($a, $b) {
                        return $this->sortTotalTimeNullLast($a, $b);
                    })->values();

                    unset($item->participants);
                    $item->participants = $tempSorted;
                    $tempOverAll =  $completed ? $item->participants->avg('total_time') : 0;
                    $item->overall_total_time = $tempOverAll;
                    return $item;
                })
                    ->sort(function ($a, $b) {
                        return $this->sortTotalTimeZeroLast($a, $b);
                    })
                    ->values();


                return CollectionHelper::paginate($usersTeamWithOverall, $per_page);
            case 'corporate-team':
                $usersCorporateTeam = Team::with(['corporate', 'participants' => function ($query) {
                    $query->withSum('moontrekkerPoints as mp_total', 'amount')
                        ->with(['raceAttempt' => function ($query) {
                            $query->whereHas('challenge', function ($q) {
                                $q->where('type', 'race');
                            });
                        }])->orderBy('first_name', 'asc')->orderBy('last_name', 'desc');
                }])->where('team_type', 'corporate')->get();


                $usersCorporateTeamOverall = collect($usersCorporateTeam)->map(function ($item) {
                    $userCollection = collect($item->participants);
                    $completed = true;
                    $tempSorted = $userCollection->map(function ($item)  use (&$completed) {
                        if (is_null($item->raceAttempt)) {
                            $completed = false;
                        }
                        $item->total_time = !is_null($item->raceAttempt) ? $item->raceAttempt->total_time : NULL;
                        unset($item->raceAttempt);
                        return $item;
                    })->sort(function ($a, $b) {
                        return $this->sortTotalTimeNullLast($a, $b);
                    })->values();

                    unset($item->participants);
                    $item->participants = $tempSorted;
                    $tempOverAll = $completed ? $item->participants->avg('total_time') : 0;
                    $item->overall_total_time = $tempOverAll;
                    return $item;
                })
                    ->sort(function ($a, $b) {
                        return $this->sortTotalTimeZeroLast($a, $b);
                    })
                    ->values();


                return CollectionHelper::paginate($usersCorporateTeamOverall, $per_page);
                break;
            default:
                abort(400);
        }
    }


    public function leaderboardCorporateCup(Request $request, $id)
    {
        $per_page = !is_null($request->per_page) ? (int) $request->per_page : 100;
        $users = User::with(['team'])
            ->withSum('moontrekkerPoints as mp_total', 'amount')
            ->whereNotNull("team_id")
            ->whereHas('team', function ($q) use ($id) {
                $q->where('corporate_id', $id);
            })
            ->whereIn("privilege", ["user", "moderator"])
            ->orderBy('mp_total', 'desc')
            ->orderBy('first_name', 'asc')
            ->orderBy('last_name', 'desc')
            ->paginate($per_page);
        return $users;
    }
}
