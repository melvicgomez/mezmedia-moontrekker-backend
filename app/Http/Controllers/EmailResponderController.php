<?php

namespace App\Http\Controllers;

// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailResponderController extends Controller
{

    public function sendMoontrekkerWelcomeMessage($user)
    {
        try {
            Mail::send(
                'email-templates.new-user-welcome-message',
                ['name' => $user->name],
                function ($message) use ($user) {
                    $message
                        ->to($user->email)
                        ->subject('Welcome to Barclays MoonTrekker 21');
                }
            );
        } catch (\Throwable $th) {
            return response(["error" => $th->getMessage()], 422);
        }
    }
}
