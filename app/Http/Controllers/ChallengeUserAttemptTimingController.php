<?php

namespace App\Http\Controllers;

use App\Models\ChallengeUserAttemptTiming;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChallengeUserAttemptTimingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
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
        if ($request->progress_id) {
            // call update function
            return $this->update($request, $request->progress_id);
        }

        $timing = new ChallengeUserAttemptTiming();
        $timing->attempt_id = $request->attempt_id;
        $timing->challenge_id = $request->challenge_id;
        $timing->user_id = $request->user_id;
        $timing->start_trail_id = $request->start_trail_id;
        $timing->end_trail_id = $request->end_trail_id;
        $timing->description = $request->description;
        $timing->scanned_qr_code = $request->scanned_qr_code ?: '';
        $timing->moontrekker_point_received = (int)$request->moontrekker_point_received ?: 0;
        $timing->distance = (int)$request->distance ?: 0;
        $timing->started_at = $request->started_at ?: null;
        $timing->ended_at = $request->ended_at ?: null;
        $timing->duration = $request->duration ?: 0;
        $timing->location_data = $request->location_data ?: '';
        $timing->submitted_at = $request->submitted_at ?: null;
        $timing->longitude = $request->longitude ?: null;
        $timing->latitude = $request->latitude ?: null;

        $timing->save();

        if (!is_null($timing->challenge_id)) {
            if ($request->hasFile('submitted_image')) {
                if ($request->file('submitted_image')->isValid()) {
                    $validator = Validator::make($request->all(), [
                        'submitted_image' => 'mimes:jpg,jpeg,png|max:10240'
                    ], [
                        'submitted_image.mimes' => 'Only jpeg, png, and jpg images are allowed',
                        'submitted_image.max' => 'Sorry! Maximum allowed size for an image is 10MB',
                    ]);

                    if (!$validator->fails()) {
                        $randomHex1 = bin2hex(random_bytes(6));
                        $randomHex2 = bin2hex(random_bytes(6));
                        $uploadDate = now()->year . "-" . now()->month . "-" . now()->day;
                        $extension = $request->submitted_image->extension();
                        $newFileName = $uploadDate . '-' . $randomHex1 . '-' . $randomHex2 . '.'  . $extension;
                        $request->submitted_image->storeAs('/public/images/attempt/' . $timing->progress_id, $newFileName);
                        $timing->update(["submitted_image" => $newFileName]);
                    } else {
                        return response(["error" => ["submitted_image" => $validator->errors()->get('submitted_image')]], 400);
                    }
                }
            }
        }

        return ["data" => $timing];
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ChallengeUserAttemptTiming
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ChallengeUserAttemptTiming
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
     * @param  \App\Models\ChallengeUserAttemptTiming
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $fieldsToUpdate = $request->only([
            'attempt_id',
            'challenge_id',
            'user_id',
            'start_trail_id',
            'end_trail_id',
            'description',
            'scanned_qr_code',
            'moontrekker_point_received',
            'distance',
            'started_at',
            'ended_at',
            'duration',
            'location_data',
            'longitude',
            'latitude',
        ]);

        $timing = ChallengeUserAttemptTiming::where('progress_id', $id)->first();

        if (!is_null($timing)) {
            $timing->update($fieldsToUpdate);
            return ["data" => $timing];
        } else {
            return ["error" => ["timing" => "User attempt timing not found."]];
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ChallengeUserAttemptTiming
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $timing = ChallengeUserAttemptTiming::find($id);
        if ($timing) {
            $timing->delete();
            return response()->json(["data" => ["attempt" => $timing]]);
        }

        return response()->json(["data" =>
        [
            "attempt" => "No attempt timing deleted."
        ]]);
    }
}
