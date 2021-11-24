<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use App\Models\ChallengeTrail;
use App\Models\ChallengeTrailImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ChallengeTrailController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
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
        if ($request->trail_id) {
            // call update function
            return $this->update($request, $request->trail_id);
        }

        $challengeInfo = Challenge::where('challenge_id', $request->challenge_id)
            ->first();

        if (!is_null($challengeInfo)) {
            $challengeTrail = new ChallengeTrail();
            $challengeTrail->challenge_id =  $challengeInfo->challenge_id;
            $challengeTrail->title = $request->title;
            $challengeTrail->description = $request->description;
            $challengeTrail->html_content = $request->html_content;
            $challengeTrail->moontrekker_point = $request->moontrekker_point ?: 0;
            $challengeTrail->distance = $request->distance ?: 0;
            $challengeTrail->trail_index = $request->trail_index;
            $challengeTrail->station_qr_value = $request->station_qr_value;
            $challengeTrail->longitude = $request->longitude;
            $challengeTrail->latitude = $request->latitude;

            $challengeTrail->save();

            if ($challengeTrail->trail_id) {

                if ($request->hasFile('trail_progress_image')) {
                    if ($request->file('trail_progress_image')->isValid()) {
                        $validator = Validator::make(array('trail_progress_image' => $request->trail_progress_image), [
                            'trail_progress_image' => 'mimes:jpg,jpeg,png|max:10240',
                        ], [
                            'trail_progress_image.mimes' => 'Only jpeg, png, and jpg images are allowed',
                            'trail_progress_image.max' => 'Sorry! Maximum allowed size for an image is 10MB',
                        ]);

                        if (!$validator->fails()) {
                            $randomHex1 = bin2hex(random_bytes(6));
                            $randomHex2 = bin2hex(random_bytes(6));
                            $uploadDate = now()->year . "-" . now()->month . "-" . now()->day;
                            $extension = $request->trail_progress_image->extension();
                            $newFileName = $uploadDate . '-' . $randomHex1 . '-' . $randomHex2 . '.'  . $extension;
                            $request->trail_progress_image->storeAs('/public/images/trail/' . $challengeTrail->trail_id, $newFileName);
                            $challengeTrail->update(["trail_progress_image" => $newFileName]);
                        } else {
                            return response(["error" => ["image" => $validator->errors()->get('images.*')]], 400);
                        }
                    }
                }

                if (is_array($request->trail_images)) {
                    $validator = Validator::make($request->trail_images, [
                        'images.*' => 'mimes:jpg,jpeg,png|max:10240'
                    ], [
                        'images.*.mimes' => 'Only jpeg, png, and jpg images are allowed',
                        'images.*.max' => 'Sorry! Maximum allowed size for an image is 10MB',
                    ]);

                    if (!$validator->fails()) {
                        foreach ($request->trail_images as $trail) {
                            if (!is_null($trail)) {
                                if (
                                    Str::lower($trail['image_filename']->getClientOriginalExtension()) == 'jpg'
                                    || Str::lower($trail['image_filename']->getClientOriginalExtension()) == 'jpeg'
                                    || Str::lower($trail['image_filename']->getClientOriginalExtension()) == 'png'
                                ) {
                                    $randomHex1 = bin2hex(random_bytes(6));
                                    $randomHex2 = bin2hex(random_bytes(6));
                                    $uploadDate = now()->year . "-" . now()->month . "-" . now()->day;
                                    $extension = $trail['image_filename']->extension();
                                    $newFileName = $uploadDate . '-' . $randomHex1 . '-' . $randomHex2 . '.'  . $extension;
                                    $trail['image_filename']->storeAs('/public/images/trail/' . $challengeTrail->trail_id, $newFileName);
                                    $trailImages = new ChallengeTrailImage();
                                    $trailImages->trail_id = $challengeTrail->trail_id;
                                    $trailImages->image_filename = $newFileName;
                                    $trailImages->description =  $trail['description'];
                                    $trailImages->save();
                                } else {
                                    return response(["error" => ["image" => 'Only jpeg, png, and jpg images are allowed']], 400);
                                }
                            }
                        }
                    } else {
                        return response(["error" => ["image" => $validator->errors()->get('images.*')]], 400);
                    }
                }
            }
        }

        $challengeInfo->trails;

        return ["data" => $challengeInfo];
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Challenge
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $trail = ChallengeTrail::find($id);
        $trail->images;
        return response(["data" => $trail]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Challenge
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
     * @param  \App\Models\Challenge
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $fieldsToUpdate = $request->only([
            'challenge_id',
            'title',
            'description',
            'html_content',
            'moontrekker_point',
            'distance',
            'trail_index',
            'station_qr_value',
            'longitude',
            'latitude'
        ]);

        $trail = ChallengeTrail::where('trail_id', $id)->first();

        if (!is_null($trail)) {
            $trail->update($fieldsToUpdate);

            if ($request->hasFile('trail_progress_image')) {
                if ($request->file('trail_progress_image')->isValid()) {
                    $validator = Validator::make($request->all(), [
                        'trail_progress_image' => 'mimes:jpg,jpeg,png|max:10240'
                    ], [
                        'trail_progress_image.mimes' => 'Only jpeg, png, and jpg images are allowed',
                        'trail_progress_image.max' => 'Sorry! Maximum allowed size for an image is 10MB',
                    ]);

                    if (!$validator->fails()) {
                        $randomHex1 = bin2hex(random_bytes(6));
                        $randomHex2 = bin2hex(random_bytes(6));
                        $uploadDate = now()->year . "-" . now()->month . "-" . now()->day;
                        $extension = $request->trail_progress_image->extension();
                        $newFileName = $uploadDate . '-' . $randomHex1 . '-' . $randomHex2 . '.'  . $extension;
                        $request->trail_progress_image->storeAs('/public/images/trail/' . $trail->trail_id, $newFileName);
                        $trail->update(["trail_progress_image" => $newFileName]);
                    } else {
                        return response(["error" => ["trail_progress_image" => $validator->errors()->get('trail_progress_image')]], 400);
                    }
                }
            }

            return ["data" => $trail];
        } else {
            return ["error" => ["trail" => "Trail not found."]];
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Challenge
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $trail = ChallengeTrail::find($id);
        if ($trail) {
            ChallengeTrailImage::where('trail_id', $trail->trail_id)->delete();

            $trail->delete();
            return response()->json(["data" => ["trail" => $trail]]);
        }

        return response()->json(["data" =>
        [
            "trail" => "No trail deleted."
        ]]);
    }
}
