<?php

namespace App\Http\Controllers;

use App\Models\ChallengeRewardLog;
use Illuminate\Http\Request;

class ChallengeRewardLogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        if ($request->log_id) {
            // call update function
            return $this->update($request, $request->log_id);
        }

        $log = new ChallengeRewardLog();
        $log->challenge_id = $request->challenge_id;
        $log->user_id = $request->user_id;
        $log->status = $request->status ?: 'completed';
        $log->save();

        return ["data" => $log];
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
            'challenge_id',
            'user_id',
            'status',
        ]);

        $log = ChallengeRewardLog::where('log_id', $id)->first();

        if (!is_null($log)) {
            $log->update($fieldsToUpdate);
            return ["data" => $log];
        } else {
            return ["error" => ["log" => "Log not found."]];
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
        $log = ChallengeRewardLog::find($id);
        if ($log) {
            $log->delete();
            return response()->json(["data" => ["log" => $log]]);
        }

        return response()->json(["data" =>
        [
            "log" => "No reward log deleted."
        ]]);
    }

    public function getLogForChallenge(Request $request)
    {
        $logs = ChallengeRewardLog::where('challenge_id', $request->challenge_id);

        return response(["data" => $logs]);
    }
}
