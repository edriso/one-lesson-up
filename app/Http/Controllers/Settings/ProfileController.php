<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
                'user' => [
                    'username' => $user->username,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'bio' => $user->bio,
                    'website_url' => $user->website_url,
                    'avatar' => $user->avatar,
                    'points' => $user->points,
                    'timezone' => $user->timezone,
                    'timezone_updated_at' => $user->timezone_updated_at,
                    'can_update_timezone' => $user->canUpdateTimezone(),
                ],
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        // Convert email to lowercase
        if (isset($validated['email'])) {
            $validated['email'] = strtolower($validated['email']);
        }
        
        // Handle timezone update separately to use the updateTimezone method
        if (isset($validated['timezone']) && $validated['timezone'] !== $request->user()->timezone) {
            $timezoneUpdated = $request->user()->updateTimezone($validated['timezone']);
            if (!$timezoneUpdated) {
                return back()->withErrors(['timezone' => 'Unable to update timezone. Please try again.']);
            }
            unset($validated['timezone']); // Remove from regular update
        }
        
        $request->user()->fill($validated);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
