<?php

namespace App\Http\Controllers;

use App\Models\Corporate;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CorporateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $corporates = Corporate::whereNull('deleted_at');
        $per_page = !is_null($request->per_page) ? (int) $request->per_page : 200;

        if (!is_null($request->search)) {
            $corporates->where('business_name', 'like', "%" . $request->search . "%");
        }

        $corporates->orderBy('corporate_id', 'desc');

        return $corporates->paginate($per_page);
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
        if ($request->corporate_id) {
            return $this->update($request, $request->corporate_id);
        }

        $corporate = new Corporate();
        $corporate->business_name = $request->business_name;
        $corporate->save();

        if (!is_null($corporate->corporate_id))
            if ($request->hasFile('logo_filename')) {
                if ($request->file('logo_filename')->isValid()) {
                    $validator = Validator::make($request->all(), [
                        'logo_filename' => 'mimes:jpg,jpeg,png|max:10240'
                    ], [
                        'logo_filename.mimes' => 'Only jpeg, png, and jpg images are allowed',
                        'logo_filename.max' => 'Sorry! Maximum allowed size for an image is 10MB',
                    ]);

                    if (!$validator->fails()) {
                        $randomHex1 = bin2hex(random_bytes(6));
                        $randomHex2 = bin2hex(random_bytes(6));
                        $uploadDate = now()->year . "-" . now()->month . "-" . now()->day;
                        $extension = $request->logo_filename->extension();
                        $newFileName = $uploadDate . '-' . $randomHex1 . '-' . $randomHex2 . '.'  . $extension;
                        $request->logo_filename->storeAs('/public/images/corporate/' . $corporate->corporate_id, $newFileName);
                        $corporate->update(["logo_filename" => $newFileName]);
                    } else {
                        return response(["error" => ["image" => $validator->errors()->get('logo_filename')]], 400);
                    }
                }
            }

        return ["data" => $corporate];
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Corporate  $corporate
     * @return \Illuminate\Http\Response
     */
    public function show(Corporate $corporate)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Corporate  $corporate
     * @return \Illuminate\Http\Response
     */
    public function edit(Corporate $corporate)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Corporate  $corporate
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $corporate = Corporate::where('corporate_id', $id)->first();
        if ($request->business_name) {
            $corporate->update(['business_name' => $request->business_name]);
        }

        if ($request->hasFile('logo_filename')) {
            if ($request->file('logo_filename')->isValid()) {
                $validator = Validator::make($request->all(), [
                    'logo_filename' => 'mimes:jpg,jpeg,png|max:10240'
                ], [
                    'logo_filename.mimes' => 'Only jpeg, png, and jpg images are allowed',
                    'logo_filename.max' => 'Sorry! Maximum allowed size for an image is 10MB',
                ]);

                if (!$validator->fails()) {
                    $randomHex1 = bin2hex(random_bytes(6));
                    $randomHex2 = bin2hex(random_bytes(6));
                    $uploadDate = now()->year . "-" . now()->month . "-" . now()->day;
                    $extension = $request->logo_filename->extension();
                    $newFileName = $uploadDate . '-' . $randomHex1 . '-' . $randomHex2 . '.'  . $extension;
                    $request->logo_filename->storeAs('/public/images/corporate/' .  $id, $newFileName);
                    $corporate->update(["logo_filename" => $newFileName]);
                } else {
                    return response(["error" => ["image" => $validator->errors()->get('logo_filename')]], 400);
                }
            }
        }

        return ["data" => $corporate];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Corporate  $corporate
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $corporate = Corporate::find($id);
        if ($corporate) {
            $teams = Team::where('corporate_id', $corporate->corporate_id)->get();
            foreach ($teams as $team) {
                $team->update(["corporate_id" =>  0, 'team_type' => 'team']);
            }
            $corporate->delete();

            return response()->json(["data" => ["corporate" => $corporate]]);
        }

        return response()->json(["data" =>
        [
            "corporate" => "No corporate deleted."
        ]]);
    }
}
