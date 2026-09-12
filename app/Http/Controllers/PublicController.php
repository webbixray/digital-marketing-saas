<?php

namespace App\Http\Controllers;

use App\Mail\ContactFormSubmission;
use App\Mail\NewsletterSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class PublicController extends Controller
{
    /**
     * Public landing page.
     */
    public function landing()
    {
        return view('public.landing');
    }

    /**
     * Pricing page.
     */
    public function pricing()
    {
        return view('public.pricing');
    }

    /**
     * Features page.
     */
    public function features()
    {
        return view('public.features');
    }

    /**
     * Documentation page.
     */
    public function docs()
    {
        return view('public.docs');
    }

    /**
     * Blog page.
     */
    public function blog()
    {
        return view('public.blog');
    }

    /**
     * Contact page.
     */
    public function contact()
    {
        return view('public.contact');
    }

    /**
     * Terms of Service page.
     */
    public function terms()
    {
        return view('public.terms');
    }

    /**
     * Privacy Policy page.
     */
    public function privacy()
    {
        return view('public.privacy');
    }

    /**
     * Welcome/onboarding landing page.
     */
    public function welcome()
    {
        return view('public.welcome');
    }

    /**
     * Handle contact form submission.
     */
    public function contactSubmit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        // Log the contact request
        Log::info('Contact form submitted', [
            'name' => $data['first_name'].' '.$data['last_name'],
            'email' => $data['email'],
            'subject' => $data['subject'],
        ]);

        // Try to send email if configured
        try {
            Mail::to(config('mail.from.address', 'hello@digitalmarketsaas.com'))
                ->send(new ContactFormSubmission(
                    firstName: $data['first_name'],
                    lastName: $data['last_name'],
                    email: $data['email'],
                    subject: $data['subject'],
                    message: $data['message'],
                ));
        } catch (\Exception $e) {
            Log::warning('Contact email failed: '.$e->getMessage());
            // Don't fail the submission if email fails
        }

        return back()->with('success', 'Thank you for your message! We\'ll get back to you within 24 hours.');
    }

    /**
     * Handle newsletter subscription.
     */
    public function newsletter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $email = $validator->validated()['email'];

        // Send confirmation email
        try {
            Mail::to($email)->send(new NewsletterSubscription($email));
        } catch (\Exception $e) {
            Log::warning('Newsletter email failed: '.$e->getMessage());
        }

        return back()->with('success', 'Thank you for subscribing! You\'ll receive our latest updates.');
    }
}
