<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $per_page = !is_null($request->per_page) ? (int) $request->per_page : 200;
        $type = $request->type ?: 'user'; // user: user site | admin: cms

        if ($type == 'user') {
            $announcements = Announcement::where('pin_post', 1)->whereNull('deleted_at')->get();
            return $announcements;
        } else {
            $announcements = Announcement::whereNull('deleted_at')->orderBy('announcement_id', 'desc');

            if (!is_null($request->search)) {
                $announcements->where(function ($q) use ($request) {
                    $q->where('title', 'like', "%" . $request->search . "%")
                        ->orWhere('description', 'like', "%" . $request->search . "%")
                        ->orWhere('html_content', 'like', "%" . $request->search . "%");
                });
            }

            return $announcements->paginate($per_page);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if ($request->announcement_id) {
            // call update function
            return $this->update($request, $request->announcement_id);
        }

        $newAnnouncement = new Announcement();
        $newAnnouncement->title = $request->title  ?: null;
        $newAnnouncement->description = $request->description  ?: null;
        $newAnnouncement->notification_message = $request->notification_message  ?: null;
        $newAnnouncement->html_content = $request->html_content  ?: null;
        $newAnnouncement->pin_post = $request->pin_post;

        $newAnnouncement->published_at = $request->published_at == 1 ? now() : null;
        $newAnnouncement->scheduled_at =  $request->scheduled_at ?: null;
        $newAnnouncement->save();

        if ($newAnnouncement->announcement_id) {
            if ($request->hasFile('cover_image')) {
                if ($request->file('cover_image')->isValid()) {
                    $validator = Validator::make($request->all(), [
                        'cover_image' => 'mimes:jpg,jpeg,png|max:10240'
                    ], [
                        'cover_image.mimes' => 'Only jpeg, png, and jpg images are allowed',
                        'cover_image.max' => 'Sorry! Maximum allowed size for an image is 10MB',
                    ]);

                    if (!$validator->fails()) {
                        $randomHex1 = bin2hex(random_bytes(6));
                        $randomHex2 = bin2hex(random_bytes(6));
                        $uploadDate = now()->year . "-" . now()->month . "-" . now()->day;
                        $extension = $request->cover_image->extension();
                        $newFileName = $uploadDate . '-' . $randomHex1 . '-' . $randomHex2 . '.'  . $extension;
                        $request->cover_image->storeAs('/public/images/announcement/' . $newAnnouncement->announcement_id, $newFileName);
                        $newAnnouncement->update(["cover_image" => $newFileName]);

                        return response([
                            "data" => "Added new announcement.",
                            "announcement_id" => $newAnnouncement->announcement_id
                        ], 200);
                    } else {
                        return response(["error" => ["image" => $validator->errors()->get('cover_image')]], 400);
                    }
                }
            }
        }
        return response(["error" => ["image" => "Image is required"]], 400);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Announcement  $announcement
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $activityFeed = Announcement::find($id);
        return response(["data" => $activityFeed]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Announcement  $announcement
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
     * @param  \App\Models\Announcement  $announcement
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $fieldsToUpdate = $request->only([
            'title',
            'content',
            'html_content',
            'scheduled_at',
            'pin_post',
            'notification_message'
        ]);

        $announcement = Announcement::find($id);

        if (!is_null($announcement)) {
            $announcement->update($fieldsToUpdate);

            $notifToUpdate = Notification::where("announcement_id", (int) $id);
            $notifToUpdate->update(['message' => $request->notification_message]);

            if ($request->published_at) {
                $announcement->update([
                    "published_at" =>  $request->published_at == 1 ? now() : null,
                ]);
            }

            if ($request->hasFile('cover_image')) {
                if ($request->file('cover_image')->isValid()) {
                    $validator = Validator::make($request->all(), [
                        'cover_image' => 'mimes:jpg,jpeg,png|max:10240'
                    ], [
                        'cover_image.mimes' => 'Only jpeg, png, and jpg images are allowed',
                        'cover_image.max' => 'Sorry! Maximum allowed size for an image is 10MB',
                    ]);

                    if (!$validator->fails()) {
                        $randomHex1 = bin2hex(random_bytes(6));
                        $randomHex2 = bin2hex(random_bytes(6));
                        $uploadDate = now()->year . "-" . now()->month . "-" . now()->day;
                        $extension = $request->cover_image->extension();
                        $newFileName = $uploadDate . '-' . $randomHex1 . '-' . $randomHex2 . '.'  . $extension;
                        $request->cover_image->storeAs('/public/images/announcement/' .  $id, $newFileName);
                        $announcement->update(["cover_image" => $newFileName]);
                    } else {
                        return response(["error" => ["image" => $validator->errors()->get('cover_image')]], 400);
                    }
                }
            }
            return ["data" => $announcement];
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Announcement  $announcement
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $recordToDelete = Announcement::find($id);
        if ($recordToDelete) {
            $recordToDelete->delete();

            $notifToDelete = Notification::where("announcement_id", (int) $id);
            $notifToDelete->delete();

            return response(null, 204);
        }
        return ["error" => ["message" => "Announcement not found."]];
    }

    public function publishPost(Request $request, $id)
    {
        $announcement = Announcement::find($id);

        if (!is_null($announcement)) {
            if ($request->action == 'publish') {
                $announcement->update([
                    "published_at" => now(),
                    "scheduled_at" => null
                ]);

                $users = User::whereIn("privilege", ["user", "moderator"])
                    ->get();
                foreach ($users as $user) {
                    $notif = new Notification();
                    $notif->title = $announcement->title;
                    $notif->message = $announcement->notification_message;
                    $notif->deep_link = "announcement/" . $announcement->announcement_id;
                    $notif->announcement_id = $announcement->announcement_id;
                    $notif->user_id = $user->user_id;
                    $notif->save();
                }

                $fcm = new FCMNotificationController();
                $fcm->sendNotificationTopic(
                    env('APP_ENV') == 'production' ? "message_all_users" : "message_all_staging_users",
                    $announcement->title,
                    $announcement->notification_message,
                    ["url" => "announcement/" . $announcement->announcement_id]
                );
                // SEND NOTIFICATION TO ALL USERS

                return ["data" => $announcement];
            } else {
                $announcement->update(["published_at" => null]);

                $notifToDelete = Notification::where("announcement_id", (int) $id);
                $notifToDelete->delete();

                return ["data" => $announcement];
            }
        }
    }

    public function scheduledPosts()
    {
        $tempNow = now()->format('Y-m-d H:i');
        $scheduledPosts = Announcement::whereNotNull('scheduled_at')
            ->where('scheduled_at', $tempNow)
            ->get();

        foreach ($scheduledPosts as $posts) {
            $request = new Request(["action" => 'publish']);
            $this->publishPost($request, $posts->announcement_id);
        }
    }
}
