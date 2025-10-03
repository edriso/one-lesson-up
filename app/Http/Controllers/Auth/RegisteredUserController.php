<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'username' => 'required|string|max:255|min:3|regex:/^[a-zA-Z0-9_]+$/',
            'avatar' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:255',
            'linkedin_url' => 'nullable|string|max:255',
            'website_url' => 'nullable|string|max:255',
            'is_public' => 'nullable|boolean',
        ]);

        // Check for case-insensitive username uniqueness
        $existingUser = User::whereRaw('LOWER(username) = ?', [strtolower($request->username)])->first();
        if ($existingUser) {
            return back()->withErrors(['username' => 'The username has already been taken.']);
        }

        // Validate avatar only if user has enough points
        if ($request->has('avatar') && $request->avatar) {
            $userPoints = 0; // New users start with 0 points
            if (!\App\Enums\PointThreshold::PROFILE_PICTURE_UNLOCK->isUnlocked($userPoints)) {
                return back()->withErrors([
                    'avatar' => 'You need at least ' . \App\Enums\PointThreshold::PROFILE_PICTURE_UNLOCK->value . ' points to upload a profile picture.'
                ]);
            }
        }

        try {
            $user = User::create([
                'username' => $request->username,
                'full_name' => $request->full_name,
                'email' => strtolower($request->email), // Convert to lowercase
                'password' => Hash::make($request->password),
                'avatar' => $request->avatar,
                'title' => $request->title,
                'bio' => $request->bio,
                'linkedin_url' => $request->linkedin_url,
                'website_url' => $request->website_url,
                'is_public' => $request->is_public ?? true,
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Handle unique constraint violations
            if (str_contains($e->getMessage(), 'users.email')) {
                return back()->withErrors(['email' => 'The email has already been taken.']);
            }
            if (str_contains($e->getMessage(), 'users.username')) {
                return back()->withErrors(['username' => 'The username has already been taken.']);
            }
            throw $e;
        }

        event(new Registered($user));

        Auth::login($user);

        return to_route('home');
    }
}
