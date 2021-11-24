<?php

namespace App\Http\Controllers;

use App\Models\FCMNotification;
use App\Models\Notification;
use App\Models\User;
use App\PusherEvents\AdminMessage;
use Illuminate\Http\Request;

class NotificationsController extends Controller
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

    public function messageDirectToUser(Request $request)
    {
        if (auth()->user()->privilege == "moderator") {
            $notif = new Notification();
            $notif->title = $request->title;
            $notif->message = $request->message;
            $notif->deep_link = $request->deep_link ?: '';
            $notif->user_id = $request->user_id;
            $notif->save();

            $tokens = FCMNotification::where('user_id', $request->user_id)
                ->pluck('fcm_token')
                ->all();
            $fcm = new FCMNotificationController();
            $fcm->sendNotification(
                $tokens,
                $notif->title,
                $notif->message,
                ["url" => $notif->deep_link]
            );
            return response(null, 200);
        }
        abort(400);
    }

    public function messageAllUsers(Request $request)
    {
        if (auth()->user()->privilege == "moderator") {
            $users = User::whereIn("privilege", ["user", "moderator"])
                ->get();
            foreach ($users as $user) {
                $notif = new Notification();
                $notif->title = $request->title;
                $notif->message = $request->message;
                $notif->deep_link = $request->deep_link ?: '';
                $notif->user_id = (int) $user->user_id;
                $notif->save();
            }

            $fcm = new FCMNotificationController();
            $fcm->sendNotificationTopic(
                env('APP_ENV') == 'production' ? "message_all_users" : "message_all_staging_users",
                $request->title,
                $request->message,
                ["url" => $request->deep_link]
            );
            return response(["users_count" => count($users)], 200);
        }
        abort(400);
    }
}
