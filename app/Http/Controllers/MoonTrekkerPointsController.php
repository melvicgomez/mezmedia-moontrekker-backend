<?php

namespace App\Http\Controllers;

use App\Models\FCMNotification;
use App\Models\MoonTrekkerPoints;
use App\Models\Notification;
use Illuminate\Http\Request;

class MoonTrekkerPointsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        if ($user) {
            $per_page = !is_null($request->per_page) ? (int) $request->per_page : 30;
            $id = $user->privilege == "moderator" && $request->user_id ?
                $request->user_id :
                $user->user_id;

            $mpLogs = MoonTrekkerPoints::where('user_id', $id)->withTrashed()
                ->orderBy('created_at', 'DESC');
            return $mpLogs->simplePaginate($per_page);
        }
        abort(400);
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
        $mpRecord = MoonTrekkerPoints::create(
            $request->only([
                "user_id",
                "challenge_id",
                "description",
                "amount",
                "attempt_id"
            ])
        );

        // send notification to the user
        $notif = new Notification();
        $notif->title = "MoonTrekker Points";
        $notif->message = $mpRecord->description;
        $notif->user_id = $mpRecord->user_id;
        $notif->record_id = $mpRecord->record_id;
        $notif->save();

        $tokens = FCMNotification::where('user_id', $mpRecord->user_id)
            ->pluck('fcm_token')
            ->all();
        $fcm = new FCMNotificationController();
        $fcm->sendNotification(
            $tokens,
            $notif->title,
            $notif->message,
            ["url" => $notif->deep_link]
        );
        // send notification to the user
        return $mpRecord;
    }

    public function update(Request $request, $id)
    {
        $fieldsToUpdate = $request->only([
            'user_id',
            'amount',
            'description',
            'challenge_id',
            'attempt_id',
            'deleted_at',
        ]);

        $mpLog = MoonTrekkerPoints::withTrashed()->find($id);
        if ($mpLog) {
            $mpLog->update($fieldsToUpdate);
            $mpLog->user;
            return $mpLog;
        }
        return response(["error" => "Moongtrekker Point transaction not found."], 400);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MoonTrekkerPoints  $moonTrekkerPoints
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = auth()->user();
        if ($user->privilege == "moderator") {
            $mpRecord = MoonTrekkerPoints::find($id);
            if ($mpRecord) {
                // delete the related notification
                $notifToDelete = Notification::where('record_id', $mpRecord->record_id);
                $notifToDelete->delete();

                // delete the mp record
                $mpRecord->delete();
                return $mpRecord;
            }
        }
        abort(400);
    }

    public function retrieveTotalMP(Request $request)
    {
        $mpLogs = MoonTrekkerPoints::where('user_id', $request->user_id)->whereNull('deleted_at')
            ->sum('amount');

        return $mpLogs;
    }
}
