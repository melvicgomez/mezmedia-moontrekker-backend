<?php

namespace App\Http\Controllers;

use App\Models\OneTimePin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class OneTimePinController extends Controller
{
    public function newOtp(Request $request)
    {
        try {
            // Send PIN to email
            $authController = new AuthController();
            $user = User::where('email', $request->email)
                ->whereIn('privilege', ['pending', 'user', 'moderator'])
                ->first();

            if ($user) {
                // invalidate all OTPs
                OneTimePin::where('user_id', $user->user_id)->delete();
                // generate a new OTP
                $otp_code = $authController->generateOTP();
                // store OTP generated
                $otp = new OneTimePin();
                $otp->user_id = $user->user_id;
                $otp->otp_code = $otp_code;
                $otp->expired_at = now()->addMinutes(10);
                $otp->save();

                $logDescrReq = new Request([
                    "user_id" =>  $user->user_id,
                    "description" => "User requested new OTP to his email."
                ]);
                $userLogs = new UserAccessLogController();
                $userLogs->store($logDescrReq);

                if ($otp->otp_id) {
                    Mail::send(
                        'email-templates.one-time-pin',
                        ['otp' => $otp_code, 'expired_at' => $otp->expired_at],
                        function ($message) use ($user) {
                            $message->to($user->email)->subject('Your MoonTrekker Verification Code');
                        }
                    );
                }
            }
            return response(null, 204);
        } catch (\Throwable $th) {
            return response(["error" => $th->getMessage()], 422);
        }
        return abort(400);
    }



    public function verifyOtp(Request $request)
    {
        $user = User::where('email', $request->email)
            ->whereIn('privilege', ['user', 'pending', 'moderator'])
            ->first();

        if ($user && $request->otp_code) {
            $userOTP = OneTimePin::where('user_id', $user->user_id)
                ->where('otp_code', $request->otp_code)
                ->where('expired_at', '>', now())
                ->where('is_used', NULL);

            $logDescrReq = new Request([
                "user_id" =>  $user->user_id,
                "description" => "Attempted to verify OTP using the code " . $request->otp_code
            ]);
            $userLogs = new UserAccessLogController();
            $userLogs->store($logDescrReq);

            if ($userOTP->count() > 0) {
                $token_result = $user->createToken("Generated personal token for user_id (" . $user->user_id . ") via verified OTP.");
                $token = $token_result->token;
                $token->expires_at = now()->addMinutes(10);
                $token->save();

                $logDescrReq = new Request([
                    "user_id" =>  $user->user_id,
                    "description" => "User successfully verified OTP using the code " . $request->otp_code
                ]);
                $userLogs = new UserAccessLogController();
                $userLogs->store($logDescrReq);

                $userOTP->update(["is_used" => 1]);

                return response([
                    "is_valid" => 1,
                    "token" => $token_result->accessToken,
                ], 200);
            } else {
                $logDescrReq = new Request([
                    "user_id" =>  $user->user_id,
                    "description" => "User failed to verify OTP using the code " . $request->otp_code
                ]);
                $userLogs = new UserAccessLogController();
                $userLogs->store($logDescrReq);
            }
        }
        abort(400);
    }
}
