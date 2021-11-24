<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Passport\Client;
use Carbon\Carbon;

class AuthController extends Controller
{

    public function generateOTP()
    {
        $pinCharacters = '0123456789BCDFGHJKLMNPQRSTVWXYZ';
        $charactersLength = strlen($pinCharacters);
        $otpStr = '';
        for ($i = 0; $i < 5; $i++) {
            $otpStr .= $pinCharacters[rand(0, $charactersLength - 1)];
        }
        return $otpStr;
    }

    public function authenticate(Request $request)
    {
        $errorCount = 0;
        $validatorError = [];
        $userNotFoundError = [];

        $validator = Validator::make($request->all(), [
            'password' => 'required',
            'username' => 'required',
        ]);


        if ($validator->fails()) {
            $errorCount += 1;
            $validatorError = [
                "username" => !isset($request->username) ? "Username is a required field." : null,
                "password" => !isset($request->password) ?  "Password is a required field." : null
            ];
        }

        $userFound = User::where('email', strtolower($request->username))
            ->whereIn('privilege', ['user', 'moderator'])
            ->count();

        if ($userFound === 0) {
            $errorCount += 1;
            $userNotFoundError = ["username" => "This email is not registered with an account."];
        }

        if ($errorCount > 0) {
            return ["error" => array_merge($validatorError, $userNotFoundError)];
        } else {
            if (Auth::attempt([
                'email' => strtolower($request->username),
                'password' => $request->password,
            ])) {
                $user = Auth::user();
                $token = Request::create(
                    '/oauth/token',
                    'POST',
                    [
                        'grant_type' => $request->grant_type,
                        'client_id' => $request->client_id,
                        'client_secret' => $request->client_secret,
                        'scope' =>  $request->scope,
                        'username' =>  strtolower($request->username),
                        'password' => $request->password,
                    ]
                );

                $logDescrReq = new Request(["user_id" => $user->user_id, "description" => "User login."]);
                $userLogs = new UserAccessLogController();
                $userLogs->store($logDescrReq);

                // $user->loadSum(['moontrekkerPoint' => function ($query) {
                //     $query->where('amount', '>', 0);
                // }], 'amount');

                // Revoke all previous tokens and keep the current access token
                $userTokens = $user->tokens
                    ->where('revoked', false)
                    ->where('expires_at', '>=', now());
                $userTokens->slice(0)
                    ->map(function ($item) {
                        $item->revoke();
                    });

                if (!is_null($user->team))
                    $user->team->corporate;
                // Revoke all previous tokens and keep the current access token
                unset($user->tokens);
                return response(
                    ["data" => [
                        "token" => json_decode(app()->handle($token)->content()),
                        "user" => $user,
                    ]],
                    200
                );
            }

            return ["error" => ["username" => null, "password" => "Email or password did not match."]];
        }
    }

    public function logout()
    {
        $user = auth()->user();
        if ($user) {
            $logDescrReq = new Request(["user_id" => auth()->user()->user_id, "description" => "User logout."]);
            $userLogs = new UserAccessLogController();
            $userLogs->store($logDescrReq);

            $user->tokens
                ->where('revoked', false)
                ->where('expires_at', '>=', now())
                ->map(function ($item) {
                    $item->revoke();
                });
            return response(null, 204);
        }
        abort(400);
    }

    public function changePassword(Request $request, $id)
    {
        $authenticatedUser = auth()->user();
        $user = User::find($id);
        if ($authenticatedUser->user_id == $user->user_id) {
            $validator = Validator::make($request->all(), [
                'new_password' => 'regex:/^(?=.{8,}$)(?=.*?[a-z])(?=.*?[A-Z])(?=.*?[0-9]).*$/',
            ], [
                'new_password.regex' => 'Invalid new password',
            ]);
            if (!$validator->fails()) {
                if (Hash::check($request->old_password, $user->password)) {
                    $new_password = Hash::make($request->new_password);
                    $user->update(["password" => $new_password]);

                    $logDescrReq = new Request(["user_id" =>  $user->user_id, "description" => "User changed password successfully."]);
                    $userLogs = new UserAccessLogController();
                    $userLogs->store($logDescrReq);

                    // // Revoke all user's access tokens
                    // $userTokens = $user->tokens
                    //     ->where('revoked', false)
                    //     ->where('expires_at', '>=', now());
                    // foreach ($userTokens as $token) {
                    //     $token->revoke();
                    // }
                    return response(null, 204);
                }
            } else {
                return response(["message" => "validator failed"], 400);
            }
        }
        return response(["message" => "use and userauth failed"], 400);
    }


    public function registerNewAccount(Request $request)
    {
        $currentUser = auth()->user();
        if (
            $currentUser->privilege == "moderator" &&
            $request->first_name &&
            $request->last_name &&
            $request->email
        ) {
            $userExist = User::where('email', $request->email)->count();
            // check if user exists
            if ($userExist == 0) {
                $user = new User();
                $user->first_name = $request->first_name;
                $user->last_name = $request->last_name;
                $user->email = $request->email;
                $user->privilege =  $request->privilege ? Str::of($request->privilege)->lower() : "pending";
                $user->uuid = (string) Str::uuid();

                if ($request->team_id) {
                    $team = Team::whereNull('deleted_at')->find($request->team_id);

                    if ($team) {
                        $user->team_id = $request->team_id;
                    }
                }
                $user->save();

                if ($user) {
                    // send an email to tell user to download the app in app store or playstore
                    $emailResponder = new EmailResponderController();
                    $emailResponder->sendMoontrekkerWelcomeMessage((object) [
                        "email" => $user->email,
                        "name" => $user->first_name . " " . $user->last_name
                    ]);
                    return response(["uuid" => $user->uuid], 200);
                }
            }
            // 422 Unprocessable Entity if user exists 
            abort(422);
        }
        abort(400);
    }


    public function userCreatePassword(Request $request)
    {
        $authenticatedUser = auth()->user();

        $validator = Validator::make($request->all(), [
            'new_password' => 'regex:/^(?=.{8,}$)(?=.*?[a-z])(?=.*?[A-Z])(?=.*?[0-9]).*$/',
        ], [
            'new_password.regex' => 'Invalid new password',
        ]);

        if (!$validator->fails()) {
            $new_password = Hash::make($request->new_password);

            $id = $authenticatedUser->user_id;
            $user = User::find($id);
            $user->update(["password" => $new_password]);

            if ($user->privilege == 'pending') {
                $user->update([
                    "privilege" => 'user',
                    "register_date" => Carbon::createFromFormat('Y-m-d H:i:s', now(), 'UTC')->format('Y-m-d H:i')
                ]);
            }

            $logDescrReq = new Request(["user_id" =>  $id, "description" => "User changed password successfully."]);
            $userLogs = new UserAccessLogController();
            $userLogs->store($logDescrReq);

            // Revoke all user's access tokens
            $userTokens = $user->tokens
                ->where('revoked', false)
                ->where('expires_at', '>=', now());
            foreach ($userTokens as $token) {
                $token->revoke();
            }

            // passport client
            $client = Client::where('password_client', 1)
                ->where('revoked', 0)
                ->first();

            $token = Request::create(
                '/oauth/token',
                'POST',
                [
                    "username" => $user->email,
                    "password" => $request->new_password,
                    'grant_type' => "password",
                    'client_id' => $client->id,
                    'client_secret' => $client->secret,
                    'scope' =>  ''
                ]
            );

            // TODO: Add other object relationship in $authenticatedUser
            return response(
                ["data" => [
                    "token" => json_decode(app()->handle($token)->content()),
                    "user" => $authenticatedUser,
                ]],
                200
            );
        }
        return abort(400);
    }
}
