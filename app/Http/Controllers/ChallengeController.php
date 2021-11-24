<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use App\Models\ChallengeTrail;
use App\Models\ChallengeUserAttempt;
use App\Models\ChallengeUserAttemptTiming;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

class ChallengeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $showByStatus = $request->show_status ?: 'all'; // all | training | race | challenge
        $order_by = $request->order_by ?: 'ended_at'; // column name
        $sort_by = $request->sort_by ?: 'desc'; // asc | desc
        $per_page = !is_null($request->per_page) ? (int) $request->per_page : 80;

        $type = $request->type ?: 'user'; // user: user site | admin: cms

        $challenges = Challenge::withCount(['trails as total_trails'])->whereNull('deleted_at');

        if ($type == 'user') {
            $challenges->whereNotNull('published_at');
        }

        if (!is_null($request->search)) {
            $challenges->where(function ($q) use ($request) {
                $q->where('title', 'like', "%" . $request->search . "%")
                    ->orWhere('description', 'like', "%" . $request->search . "%")
                    ->orWhere('html_content', 'like', "%" . $request->search . "%");
            });
        }

        if ($showByStatus == 'training') {
            $challenges
                ->where('type', 'training');
            $challenges->with(['trails']);  // show all trails
        }

        if ($showByStatus == 'race') {
            $challenges
                ->where('type', 'race');
            $challenges->with(['trails']); // show all trails
        }

        if ($showByStatus == 'challenge') {
            $challenges
                ->where('type', '!=', 'race')->where('type', '!=', 'training');
        }

        $challenges->orderBy($order_by, $sort_by);

        return $challenges->paginate($per_page);
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
        if ($request->challenge_id) {
            // call update function
            return $this->update($request, $request->challenge_id);
        }

        $challenge = new Challenge();
        $challenge->title = $request->title;
        $challenge->description = $request->description;
        $challenge->html_content = $request->html_content ?: null;
        $challenge->difficulty = (int)$request->difficulty ?: 0;
        $challenge->multiple_attempt_available = (int)$request->multiple_attempt_available ?: 0;
        $challenge->moontrekker_point = $request->moontrekker_point ?: 0;
        $challenge->notification_message = $request->notification_message;
        $challenge->trail_overview_html = $request->trail_overview_html ?: '';
        $challenge->distance = (int)$request->distance ?: 0;
        $challenge->reward_count = (int)$request->reward_count ?: 0;
        $challenge->is_time_required = (int)$request->is_time_required ?: 0;
        $challenge->is_distance_required = (int)$request->is_distance_required ?: 0;
        $challenge->type = $request->type;
        $challenge->started_at =  $request->started_at ?: null;
        $challenge->ended_at =  $request->ended_at ?: null;
        $challenge->schedule_at =  $request->schedule_at ?: null;

        $challenge->save();

        $errorCount = 0;
        $challengeImageValidatorError = [];
        $checkpointImageValidatorError = [];

        if (!is_null($challenge->challenge_id)) {

            if ($request->hasFile('challenge_cover_image')) {
                if ($request->file('challenge_cover_image')->isValid()) {
                    $validator = Validator::make($request->all(), [
                        'challenge_cover_image' => 'mimes:jpg,jpeg,png|max:10240'
                    ], [
                        'challenge_cover_image.mimes' => 'Only jpeg, png, and jpg images are allowed',
                        'challenge_cover_image.max' => 'Sorry! Maximum allowed size for an image is 10MB',
                    ]);

                    if (!$validator->fails()) {
                        $randomHex1 = bin2hex(random_bytes(6));
                        $randomHex2 = bin2hex(random_bytes(6));
                        $uploadDate = now()->year . "-" . now()->month . "-" . now()->day;
                        $extension = $request->challenge_cover_image->extension();
                        $newFileName = $uploadDate . '-' . $randomHex1 . '-' . $randomHex2 . '.'  . $extension;
                        $request->challenge_cover_image->storeAs('/public/images/challenge/' . $challenge->challenge_id, $newFileName);
                        $challenge->update(["challenge_cover_image" => $newFileName]);
                    } else {
                        $errorCount += 1;
                        $challengeImageValidatorError = ["challenge_cover_image" => $validator->errors()->get('challenge_cover_image')];
                    }
                }
            }

            if ($request->hasFile('checkpoint_preview_image')) {
                if ($request->file('checkpoint_preview_image')->isValid()) {
                    $validator = Validator::make($request->all(), [
                        'checkpoint_preview_image' => 'mimes:jpg,jpeg,png|max:10240',
                    ], [
                        'checkpoint_preview_image.mimes' => 'Only jpeg, png, and jpg images are allowed',
                        'checkpoint_preview_image.max' => 'Sorry! Maximum allowed size for an image is 10MB',
                    ]);

                    if (!$validator->fails()) {
                        $randomHex1 = bin2hex(random_bytes(6));
                        $randomHex2 = bin2hex(random_bytes(6));
                        $uploadDate = now()->year . "-" . now()->month . "-" . now()->day;
                        $extension = $request->checkpoint_preview_image->extension();
                        $newFileName = $uploadDate . '-' . $randomHex1 . '-' . $randomHex2 . '.'  . $extension;
                        $request->checkpoint_preview_image->storeAs('/public/images/challenge/' . $challenge->challenge_id, $newFileName);
                        $challenge->update(["checkpoint_preview_image" => $newFileName]);
                    } else {
                        $errorCount += 1;
                        $checkpointImageValidatorError = ["checkpoint_preview_image" => $validator->errors()->get('checkpoint_preview_image')];
                    }
                }
            }
        }

        if ($errorCount > 0) {
            return ["error" => array_merge($challengeImageValidatorError, $checkpointImageValidatorError)];
        } else {
            return array_merge(["data" => $challenge]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Challenge
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $challenge = Challenge::with(['trails'])->withCount([
            'participants as total_reward_claimed',
            'participants as is_joined_challenge' => function ($query) {
                $query->where('user_id', auth()->user()->user_id);
            },
        ])->find($id);

        if ($challenge->type == 'race' || $challenge->type == 'training') {
            unset($challenge->total_reward_claimed);
            unset($challenge->is_joined_challenge);
        }

        $challenge->trails;

        return response(["data" => $challenge]);
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
            'title',
            'description',
            'html_content',
            'difficulty',
            'multiple_attempt_available',
            'moontrekker_point',
            'notification_message',
            'distance',
            'reward_count',
            'is_time_required',
            'is_distance_required',
            'type',
            'trail_overview_html',
            'checkpoint_preview_image',
            'challenge_cover_image',
            'started_at',
            'ended_at',
            'schedule_at',
        ]);

        $challenge = Challenge::where('challenge_id', $id)->first();

        if (!is_null($challenge)) {
            $challenge->update($fieldsToUpdate);

            $errorCount = 0;
            $challengeImageValidatorError = [];
            $checkpointImageValidatorError = [];

            if ($request->hasFile('challenge_cover_image')) {
                if ($request->file('challenge_cover_image')->isValid()) {
                    $validator = Validator::make($request->all(), [
                        'challenge_cover_image' => 'mimes:jpg,jpeg,png|max:10240'
                    ], [
                        'challenge_cover_image.mimes' => 'Only jpeg, png, and jpg images are allowed',
                        'challenge_cover_image.max' => 'Sorry! Maximum allowed size for an image is 10MB',
                    ]);

                    if (!$validator->fails()) {
                        $randomHex1 = bin2hex(random_bytes(6));
                        $randomHex2 = bin2hex(random_bytes(6));
                        $uploadDate = now()->year . "-" . now()->month . "-" . now()->day;
                        $extension = $request->challenge_cover_image->extension();
                        $newFileName = $uploadDate . '-' . $randomHex1 . '-' . $randomHex2 . '.'  . $extension;
                        $request->challenge_cover_image->storeAs('/public/images/challenge/' . $challenge->challenge_id, $newFileName);
                        $challenge->update(["challenge_cover_image" => $newFileName]);
                    } else {
                        $errorCount += 1;
                        $challengeImageValidatorError = ["challenge_cover_image" => $validator->errors()->get('challenge_cover_image')];
                    }
                }
            }

            if ($request->hasFile('checkpoint_preview_image')) {
                if ($request->file('checkpoint_preview_image')->isValid()) {
                    $validator = Validator::make($request->all(), [
                        'checkpoint_preview_image' => 'mimes:jpg,jpeg,png|max:10240',
                    ], [
                        'checkpoint_preview_image.mimes' => 'Only jpeg, png, and jpg images are allowed',
                        'checkpoint_preview_image.max' => 'Sorry! Maximum allowed size for an image is 10MB',
                    ]);

                    if (!$validator->fails()) {
                        $randomHex1 = bin2hex(random_bytes(6));
                        $randomHex2 = bin2hex(random_bytes(6));
                        $uploadDate = now()->year . "-" . now()->month . "-" . now()->day;
                        $extension = $request->checkpoint_preview_image->extension();
                        $newFileName = $uploadDate . '-' . $randomHex1 . '-' . $randomHex2 . '.'  . $extension;
                        $request->checkpoint_preview_image->storeAs('/public/images/challenge/' . $challenge->challenge_id, $newFileName);
                        $challenge->update(["checkpoint_preview_image" => $newFileName]);
                    } else {
                        $errorCount += 1;
                        $checkpointImageValidatorError = ["checkpoint_preview_image" => $validator->errors()->get('checkpoint_preview_image')];
                    }
                }
            }

            if ($errorCount > 0) {
                return ["error" => array_merge($challengeImageValidatorError, $checkpointImageValidatorError)];
            } else {
                return ["data" => $challenge];
            }
        } else {
            return ["error" => ["challenge" => "Challenge not found."]];
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
        $challenge = Challenge::find($id);
        if ($challenge) {
            if ($challenge->published_at) {
                ChallengeTrail::where('challenge_id', $challenge->challenge_id)->delete();
                ChallengeUserAttempt::where('challenge_id', $challenge->challenge_id)->delete();
                ChallengeUserAttemptTiming::where('challenge_id', $challenge->challenge_id)->delete();

                $deleteNotif = Notification::where('challenge_id', $challenge->challenge_id);
                $deleteNotif->delete();
            }

            $challenge->delete();
            return response()->json(["data" => ["challenge" => $challenge]]);
        }

        return response()->json(["data" =>
        [
            "challenge" => "No challenge deleted."
        ]]);
    }

    public function retrieveChallengeList(Request $request)
    {
        $per_page = !is_null($request->per_page) ? (int) $request->per_page : 80;
        $page = !is_null($request->page) ? (int) $request->page : 1;

        $challenges = Challenge::whereNull('deleted_at')->whereNotNull('published_at')
            ->where('type', '!=', 'race')->where('type', '!=', 'training')
            ->withCount([
                'participants as total_reward_claimed',
                'participants as is_joined_challenge' => function ($query) {
                    $query->where('user_id', auth()->user()->user_id);
                },
            ]);

        $challenges->orderBy('ended_at', 'asc');

        $endedChallenges = clone $challenges;
        $endedChallenges = $endedChallenges
            ->orderBy('ended_at', 'asc')
            ->where('ended_at', '<', now())->get();

        $activeChallenges = clone $challenges;
        $activeChallenges = $activeChallenges
            ->orderBy('ended_at', 'desc')
            ->where('ended_at', '>=', now())->get();
        $responseObject = $activeChallenges->merge($endedChallenges);

        $paginator = new Paginator($responseObject->forPage($page, $per_page)->values(), $responseObject->count(), $per_page, $page);

        return $paginator;
    }

    public function publishChallenge(Request $request, $id)
    {
        $challenge = Challenge::find($id);
        if (!is_null($challenge)) {
            if ($request->action == 'publish') {
                $challenge->update([
                    "published_at" => now(),
                    "schedule_at" => null
                ]);
            } else {
                $challenge->update(["published_at" => null]);
            }

            return ["data" => $challenge];
        }
    }

    public function scheduleChallenge()
    {
        $tempNow = now()->format('Y-m-d H:i');
        $scheduledChallenges = Challenge::whereNotNull('schedule_at')
            ->where('schedule_at', $tempNow)
            ->get();

        foreach ($scheduledChallenges as $challenge) {
            $request = new Request(["action" => 'publish']);
            $this->publishChallenge($request, $challenge->challenge_id);
        }
    }
}
