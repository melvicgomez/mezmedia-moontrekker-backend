<?php

namespace App\Http\Controllers;

use App\Models\Corporate;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $showByStatus = $request->show_status ?: 'all'; // all | duo | team | corporate
        $per_page = !is_null($request->per_page) ? (int) $request->per_page : 200;

        $teams = Team::with(['corporate', 'participants'])->whereNull('deleted_at');

        if (!is_null($request->search)) {
            $teams->where('team_name', 'like', "%" . $request->search . "%");
        }

        if ($showByStatus != "all") {
            $teams->where('team_type', $showByStatus);
        }

        $teams->orderBy('team_id', 'desc');

        return $teams->paginate($per_page);
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
        if ($request->team_id) {
            return $this->update($request, $request->team_id);
        }

        $team = new Team();
        $team->team_name = $request->team_name;
        $team->team_type = $request->team_type;

        if ($request->team_type == 'corporate' && $request->corporate_id) {
            $corporate = Corporate::whereNull('deleted_at')->find($request->corporate_id);
            if ($corporate) {
                $team->corporate_id = $request->corporate_id;
            } else {
                $team->team_type = 'team';
            }
        }

        $team->save();

        return ["data" => $team];
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Team  $team
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $team = Team::find($id);
        $team->participants;
        if ($team)
            return ["data" => $team];
        return response()->json(["error" => "No team found."], 400);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Team  $team
     * @return \Illuminate\Http\Response
     */
    public function edit(Team $team)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Team  $team
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $team = Team::where('team_id', $id)->first();

        if ($request->team_type == 'corporate' && $request->corporate_id) {
            $corporate = Corporate::whereNull('deleted_at')->find($request->corporate_id);

            if ($corporate) {
                $fieldsToUpdate = $request->only([
                    'team_name',
                    'team_type',
                    'corporate_id'
                ]);

                $team->update($fieldsToUpdate);
            } else {
                $team->update(['team_name' => $request->team_name, 'team_type' => 'team', 'corporate_id' => null]);
            }
        }

        return ["data" => $team];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Team  $team
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $team = Team::find($id);
        if ($team) {
            $team->delete();
            return response()->json(["data" => ["team" => $team]]);
        }

        return response()->json(["data" =>
        [
            "team" => "No team deleted."
        ]]);
    }

    public function joinOrLeaveTeam(Request $request)
    {
        $team = Team::find($request->team_id);
        $member = User::find($request->user_id);

        if ($team) {
            if ($request->status == "join") {
                $teamMembers = User::where('team_id', $team->team_id)->get();

                if (
                    ($team->team_type == 'duo' && count($teamMembers) < 2) ||
                    (($team->team_type == 'team' || $team->team_type == 'corporate') && count($teamMembers) < 4)
                ) {
                    $member->update(["team_id" =>  $team->team_id]);
                } else {
                    return response(["error" => "Current team is now full with " + $team->team_type == 'duo' ? "2" : "4" + "members."], 400);
                }

                return ["data" => $member];
            } else {
                $member->update(["team_id" =>  0]);
            }
        } else
            return response()->json(["error" => "No team found."], 400);
    }
}
