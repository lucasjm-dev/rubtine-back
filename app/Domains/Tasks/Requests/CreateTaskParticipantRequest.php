<?php

namespace App\Domains\Tasks\Requests;

use App\Domains\Tasks\Enums\TaskParticipantProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTaskParticipantRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'user_id' => 'nullable|integer|exists:users,id',
            'participant_profile' => [
                'required_with:user_id',
                'nullable',
                'string',
                Rule::in(TaskParticipantProfile::values()),
            ],
        ];
    }
}
