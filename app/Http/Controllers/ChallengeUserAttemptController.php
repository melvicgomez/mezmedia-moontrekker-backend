<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use App\Models\ChallengeRewardLog;
use App\Models\ChallengeUserAttempt;
use App\Models\ChallengeUserAttemptTiming;
use App\Models\MoonTrekkerPoints;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ChallengeUserAttemptController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $order_by = $request->order_by ?: 'ended_at'; // column name
        $sort_by = $request->sort_by ?: 'desc'; // asc | desc
        $per_page = !is_null($request->per_page) ? (int) $request->per_page : 100;

        $attempts = ChallengeUserAttempt::whereNull('deleted_at');

        if ($request->challenge_id) {
            $challenge = Challenge::with(['trails'])->where('challenge_id', $request->challenge_id)->first();
            $attempts->where('challenge_id', $request->challenge_id)->with(['user', 'progress']);

            if (isset($request->search)) {
                $attempts->whereHas('user', function ($query) use ($request) {
                    $query->where('first_name', 'like', "%" . $request->search . "%")
                        ->orWhere('last_name', 'like', "%" . $request->search . "%")
                        ->orWhere('email', 'like', "%" . $request->search . "%");
                });
            }

            $attempts->orderBy($order_by, $sort_by);

            $responseObject = collect(
                ['challenge' => $challenge]
            );

            return $responseObject->merge($attempts->paginate($per_page));
        } else {
            $attempts->orderBy($order_by, $sort_by);
            return $attempts->paginate($per_page);
        }
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
        if ($request->attempt_id) {
            // call update function
            return $this->update($request, $request->attempt_id);
        }

        $type = $request->type ?: 'user'; // user: user site | admin: cms
        $user_id = $request->user_id;

        $attempt = new ChallengeUserAttempt();
        $attempt->challenge_id = $request->challenge_id;
        $attempt->user_id = $user_id;
        $attempt->total_distance = (int)$request->total_distance ?: 0;
        $attempt->moontrekker_point = (int)$request->moontrekker_point ?: 0;
        $attempt->status = $request->status;
        $attempt->total_time = (int)$request->total_time ?: 0;
        $attempt->started_at = $request->started_at;
        $attempt->ended_at = $request->ended_at ?: null;

        $attempt->save();

        if (!is_null($request->challenge_id) && !is_null($user_id)) {
            $challenge = Challenge::where('challenge_id', $request->challenge_id)->first();
            $user = User::where('user_id', $user_id)->first();

            if ($type == 'user') {
                if ($request->status == "complete") {
                    if ($challenge->type != 'training' && $challenge->type != 'race') {
                        try {
                            Mail::send(
                                'email-templates.challenge-completion',
                                [
                                    'name' => $user->first_name . " " . $user->last_name,
                                    'email' => $user->email,
                                    "challenge_name" => $challenge->title,
                                    "ended_at" => $attempt->ended_at
                                ],
                                function ($message) use ($user, $challenge) {
                                    $message
                                        ->to('mezmedia@gmail.com')
                                        ->subject($user->first_name . " " . $user->last_name . " " . $user->email . ' has completed ' . $challenge->title);
                                }
                            );

                            Mail::send(
                                'email-templates.challenge-completion-user',
                                [
                                    'name' => $user->first_name . " " . $user->last_name,
                                    "challenge_name" => $challenge->title,
                                ],
                                function ($message) use ($user, $challenge) {
                                    $message
                                        ->to($user->email)
                                        ->subject("Congrats! You've completed the " . $challenge->title);
                                }
                            );
                        } catch (\Throwable $th) {
                            return response(["error" => $th->getMessage()], 422);
                        }
                    }

                    if ($challenge->reward_count > 0) {
                        $rewardLog = ChallengeRewardLog::where('challenge_id', $request->challenge_id)->get()->count();
                        $log = new ChallengeRewardLog();
                        $log->challenge_id = $challenge->challenge_id;
                        $log->user_id = $user->user_id;
                        $log->status = $rewardLog < $challenge->reward_count ? 'completed' : 'completed_excess';
                        $log->save();
                    }
                }
            }

            if (isset($request->moontrekker_point) &&  $request->moontrekker_point > 0) {
                $mp = new MoonTrekkerPoints();
                $mp->user_id = $user->user_id;
                $mp->challenge_id = $challenge->challenge_id;
                $mp->description = "Awarded " . $attempt->moontrekker_point . " Moontrekker Points for completing " . $challenge->title;
                $mp->amount = $attempt->moontrekker_point;
                $mp->attempt_id = $attempt->attempt_id;
                $mp->save();
            }
        }

        return ["data" => $attempt];
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ChallengeUserAttempt
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $attempt = ChallengeUserAttempt::with(['challenge'])->find($id);
        $attempt->progress;

        return response(["data" => $attempt]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ChallengeUserAttempt
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
     * @param  \App\Models\ChallengeUserAttempt
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $fieldsToUpdate = $request->only([
            'challenge_id',
            'user_id',
            'moontrekker_point',
            'total_distance',
            'status',
            'total_time',
            'started_at',
            'ended_at',
        ]);

        $type = $request->type ?: 'user'; // user: user site | admin: cms
        $attempt = ChallengeUserAttempt::where('attempt_id', $id)->first();

        if (!is_null($attempt)) {
            $attempt->update($fieldsToUpdate);
            $challenge = Challenge::where('challenge_id', $attempt->challenge_id)->first();
            $user = User::where('user_id', $attempt->user_id)->first();

            if ($type == 'user') {
                if ($request->status == "complete") {
                    if (!is_null($challenge) && !is_null($user)) {
                        if ($challenge->type != 'training' && $challenge->type != 'race') {
                            try {
                                Mail::send(
                                    'email-templates.challenge-completion',
                                    [
                                        'name' => $user->first_name . " " . $user->last_name,
                                        'email' => $user->email,
                                        "challenge_name" => $challenge->title,
                                        "ended_at" => $attempt->ended_at,
                                    ],
                                    function ($message) use ($user, $challenge) {

                                        $message
                                            ->to('mezmedia@gmail.com')
                                            ->subject($user->first_name . " " . $user->last_name . " " . $user->email . ' has completed ' . $challenge->title);
                                    }
                                );

                                Mail::send(
                                    'email-templates.challenge-completion-user',
                                    [
                                        'name' => $user->first_name . " " . $user->last_name,
                                        "challenge_name" => $challenge->title,
                                    ],
                                    function ($message) use ($user, $challenge) {
                                        $message
                                            ->to($user->email)
                                            ->subject("Congrats! You've completed the " . $challenge->title);
                                    }
                                );
                            } catch (\Throwable $th) {
                                return response(["error" => $th->getMessage()], 422);
                            }

                            if ($challenge->reward_count > 0) {
                                $rewardLog = ChallengeRewardLog::where('challenge_id', $request->challenge_id)->get()->count();
                                $log = new ChallengeRewardLog();
                                $log->challenge_id = $challenge->challenge_id;
                                $log->user_id = $user->user_id;
                                $log->status = $rewardLog < $challenge->reward_count ? 'completed' : 'completed_excess';
                                $log->save();
                            }
                        }
                    }
                }
            }

            if (isset($request->moontrekker_point) &&  $request->moontrekker_point > 0) {
                $existingRecord = MoonTrekkerPoints::where('attempt_id', $attempt->attempt_id)->first();

                if ($existingRecord) {
                    $existingRecord->update([
                        'amount' => $attempt->moontrekker_point,
                        "description" => "Awarded " . $attempt->moontrekker_point . " Moontrekker Points for completing " . $challenge->title,
                    ]);
                } else {
                    $mpRecord = new Request([
                        "user_id" => $user->user_id,
                        "description" => "Awarded " . $attempt->moontrekker_point . " Moontrekker Points for completing " . $challenge->title,
                        "amount" => $attempt->moontrekker_point,
                        "challenge_id" => $challenge->challenge_id,
                        'attempt_id' => $attempt->attempt_id,
                    ]);

                    $mp = new MoonTrekkerPointsController();
                    $mp->store($mpRecord);
                }
            }

            return ["data" => $attempt];
        } else {
            return ["error" => ["attempt" => "User attempt not found."]];
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ChallengeUserAttempt
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $attempt = ChallengeUserAttempt::find($id);
        if ($attempt) {
            ChallengeUserAttemptTiming::where('attempt_id', $attempt->attempt_id)->delete();
            MoonTrekkerPoints::where('attempt_id', $attempt->attempt_id)->delete();

            $attempt->delete();
            return response()->json(["data" => ["attempt" => $attempt]]);
        }

        return response()->json(["data" =>
        [
            "attempt" => "No attempt deleted."
        ]]);
    }

    public function getAttemptbyChallengeId($id)
    {
        $attempt = ChallengeUserAttempt::where('challenge_id', $id)->where('user_id', auth()->user()->user_id)->where('status', 'complete')->orderBy('ended_at', 'desc')->first();

        return response(["data" => $attempt]);
    }
}
