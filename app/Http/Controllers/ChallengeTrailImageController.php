<?php

namespace App\Http\Controllers;

use App\Models\ChallengeTrail;
use App\Models\ChallengeTrailImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChallengeTrailImageController extends Controller
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
        if ($request->image_id) {
            // call update function
            return $this->update($request, $request->image_id);
        }

        $image = new ChallengeTrailImage();
        $image->trail_id =  $request->trail_id ?: null;
        $image->description =  $request->description ?: '';

        $image->save();

        if (!is_null($image->image_id)) {
            if ($request->hasFile('image_filename')) {
                if ($request->file('image_filename')->isValid()) {
                    $validator = Validator::make($request->all(), [
                        'image_filename' => 'mimes:jpg,jpeg,png|max:10240'
                    ], [
                        'image_filename.mimes' => 'Only jpeg, png, and jpg images are allowed',
                        'image_filename.max' => 'Sorry! Maximum allowed size for an image is 10MB',
                    ]);

                    if (!$validator->fails()) {
                        $randomHex1 = bin2hex(random_bytes(6));
                        $randomHex2 = bin2hex(random_bytes(6));
                        $uploadDate = now()->year . "-" . now()->month . "-" . now()->day;
                        $extension = $request->image_filename->extension();
                        $newFileName = $uploadDate . '-' . $randomHex1 . '-' . $randomHex2 . '.'  . $extension;
                        $request->image_filename->storeAs('/public/images/trail/' . $image->trail_id, $newFileName);
                        $image->update(["image_filename" => $newFileName]);
                    } else {
                        return response(["error" => ["image_filename" => $validator->errors()->get('image_filename')]], 400);
                    }
                }
            }
        }

        return ["data" => $image];
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
        $image = ChallengeTrailImage::where('image_id', $id)->first();

        if (!is_null($image->image_id)) {
            if (isset($request->description)) {
                $image->update(["description" => $request->description ?: '']);
            }

            if ($request->hasFile('image_filename')) {
                if ($request->file('image_filename')->isValid()) {
                    $validator = Validator::make($request->all(), [
                        'image_filename' => 'mimes:jpg,jpeg,png|max:10240'
                    ], [
                        'image_filename.mimes' => 'Only jpeg, png, and jpg images are allowed',
                        'image_filename.max' => 'Sorry! Maximum allowed size for an image is 10MB',
                    ]);

                    if (!$validator->fails()) {
                        $randomHex1 = bin2hex(random_bytes(6));
                        $randomHex2 = bin2hex(random_bytes(6));
                        $uploadDate = now()->year . "-" . now()->month . "-" . now()->day;
                        $extension = $request->image_filename->extension();
                        $newFileName = $uploadDate . '-' . $randomHex1 . '-' . $randomHex2 . '.'  . $extension;
                        $request->image_filename->storeAs('/public/images/trail/' . $image->trail_id, $newFileName);
                        $image->update(["image_filename" => $newFileName]);
                    } else {
                        return response(["image_filename" => $validator->errors()->get('image_filename')]);
                    }
                }
            }
        }

        return ["data" => $image];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $image = ChallengeTrailImage::find($id);
        if ($image) {
            $image->delete();
            return response()->json(["data" => ["trail_image" => $image]]);
        }

        return response()->json(["data" =>
        [
            "trail_image" => "No trail image deleted."
        ]]);
    }
}
