<?php

namespace App\Http\Controllers;

use App\Models\ContactForm;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactFormController extends Controller
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ContactForm  $contactForm
     * @return \Illuminate\Http\Response
     */
    public function show(ContactForm $contactForm)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ContactForm  $contactForm
     * @return \Illuminate\Http\Response
     */
    public function edit(ContactForm $contactForm)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ContactForm  $contactForm
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ContactForm $contactForm)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ContactForm  $contactForm
     * @return \Illuminate\Http\Response
     */
    public function destroy(ContactForm $contactForm)
    {
        //
    }

    public function contactForm(Request $request)
    {
        try {
            $user = User::where('user_id', auth()->user()->user_id)->first();
            $contactform = new ContactForm();
            $contactform->user_id = $user->user_id;
            $contactform->subject = 'Message from MoonTrekker app Contact Form';
            $contactform->description = $request->description;
            $contactform->name = $user->first_name . " " . $user->last_name;
            $contactform->email = $user->email;
            $contactform->save();

            if ($contactform->support_id) {
                Mail::send(
                    'email-templates.contact-form-user-email',
                    [
                        'name' => $contactform->name,
                    ],
                    function ($message) use ($contactform) {
                        $message->to($contactform->email)->subject('Thank you for your enquiry!');
                    }
                );
                Mail::send(
                    'email-templates.contact-form-support-email',
                    [
                        'contactform' => $contactform,
                    ],
                    function ($message) {
                        $message
                            ->to('mezmedia@gmail.com')
                            ->subject('You’ve received an enquiry from the Contact form');
                    }
                );
                return response(null, 200);
            }
        } catch (\Throwable $th) {
            return response(["error" => $th->getMessage()], 422);
        }
    }
}
