<?php

namespace App\Http\Requests\Settings;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:255', 'min:3', 'regex:/^[a-zA-Z0-9_]+$/'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'avatar' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:255'],
            'website_url' => ['nullable', 'string', 'max:255'],
            'is_public' => ['nullable', 'boolean'],
            'timezone' => ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) {
                if ($value && !in_array($value, timezone_identifiers_list())) {
                    $fail('The selected timezone is invalid.');
                }
            }],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate avatar only if user has enough points
            if ($this->has('avatar') && $this->avatar) {
                $userPoints = $this->user()->points ?? 0;
                if (!\App\Enums\PointThreshold::PROFILE_PICTURE_UNLOCK->isUnlocked($userPoints)) {
                    $validator->errors()->add(
                        'avatar',
                        'You need at least ' . \App\Enums\PointThreshold::PROFILE_PICTURE_UNLOCK->value . ' points to upload a profile picture.'
                    );
                }
            }

            // Validate timezone update frequency (30 days)
            if ($this->has('timezone') && $this->timezone && $this->timezone !== $this->user()->timezone) {
                if (!$this->user()->canUpdateTimezone()) {
                    $nextUpdateDate = $this->user()->timezone_updated_at->addDays(30)->format('F j, Y');
                    $validator->errors()->add(
                        'timezone',
                        "Timezone can be updated again on {$nextUpdateDate}."
                    );
                }
            }
        });
    }
}
