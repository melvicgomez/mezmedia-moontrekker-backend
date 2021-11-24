<?php

namespace App\Http\Controllers;

use App\Models\BadWeather;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BadWeatherController extends Controller
{


    public function index(Request $request)
    {
        $type = $request->type ?: 'user';
        $per_page = !is_null($request->per_page) ? (int) $request->per_page : 200;

        if ($type === 'user') {
            $now = Carbon::parse(now('UTC')->format('Y-m-d H:i'));
            $badWeather = BadWeather::where('ended_at', '>=', $now)->where('started_at', '<=', $now)->orderBy('ended_at', 'asc');
            return $badWeather->get();
        } else {
            $announcements = BadWeather::whereNull('deleted_at')->orderBy('warning_id', 'desc');

            if (!is_null($request->search)) {
                $announcements->where(function ($q) use ($request) {
                    $q->where('title', 'like', "%" . $request->search . "%")
                        ->orWhere('message', 'like', "%" . $request->search . "%");
                });
            }

            return $announcements->paginate($per_page);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $badWeather = new BadWeather();
        $badWeather->title = $request->title;
        $badWeather->message = $request->message;
        $badWeather->started_at = $request->started_at;
        $badWeather->ended_at = $request->ended_at;
        $badWeather->save();

        if (is_null($badWeather->warning_id)) {
            $start = Carbon::parse($badWeather->started_at);
            $now = Carbon::parse(now('UTC')->format('Y-m-d H:i'));

            if ($start->isBefore($now)) {
                // SEND NOTIFICATION TO ALL USERS
                $users = User::whereIn("privilege", ["user", "moderator"])
                    ->get();
                foreach ($users as $user) {
                    $notif = new Notification();
                    $notif->title = $badWeather->title;
                    $notif->message = $badWeather->message;
                    $notif->user_id = $user->user_id;
                    $notif->save();
                }

                $fcm = new FCMNotificationController();
                $fcm->sendNotificationTopic(
                    env('APP_ENV') == 'production' ? "message_all_users" : "message_all_staging_users",
                    $badWeather->title,
                    $badWeather->message,
                    [
                        "bad_weather_status" => "enabled",
                        "title" => $badWeather->title,
                        "message" => $badWeather->message,
                        "started_at" => $badWeather->started_at,
                        "ended_at" =>  $badWeather->ended_at,
                    ]
                );
                // SEND NOTIFICATION TO ALL USERS
            }
        }
        return $badWeather;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\BadWeather  $badWeather
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $badWeather = BadWeather::find($id);
        if (!is_null($badWeather)) {
            return $badWeather;
        }
        abort(400);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BadWeather  $badWeather
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $weatherUpdate = BadWeather::find($id);
        if (!is_null($weatherUpdate)) {
            $fieldsToUpdate = $request->only(['title', 'message', 'started_at', 'ended_at']);
            $weatherUpdate->update($fieldsToUpdate);
            return $weatherUpdate;
        }
        abort(400);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BadWeather  $badWeather
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $test = BadWeather::find($id);
        if (!is_null($test)) {
            $test->delete();
            return response(null, 204);
        }
        abort(400);
    }

    public function notifyWarning()
    {
        $tempNow = now()->format('Y-m-d H:i');
        $badWeather = BadWeather::where('started_at', $tempNow)
            ->first();

        if ($badWeather) {
            $users = User::whereIn("privilege", ["user", "moderator"])
                ->get();
            foreach ($users as $user) {
                $notif = new Notification();
                $notif->title = $badWeather->title;
                $notif->message = $badWeather->message;
                $notif->user_id = $user->user_id;
                $notif->save();
            }

            $fcm = new FCMNotificationController();
            $fcm->sendNotificationTopic(
                env('APP_ENV') == 'production' ? "message_all_users" : "message_all_staging_users",
                $badWeather->title,
                $badWeather->message,
                [
                    "bad_weather_status" => "enabled",
                    "title" => $badWeather->title,
                    "message" => $badWeather->message,
                    "started_at" => $badWeather->started_at,
                    "ended_at" =>  $badWeather->ended_at,
                ]
            );
        }
    }
}
