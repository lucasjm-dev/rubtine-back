<?php

namespace App\Domains\Tasks\Requests;

use App\Domains\Tasks\Enums\TaskParticipantRole;
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
            'role' => [
                'nullable',
                'string',
                Rule::in([
                    TaskParticipantRole::SIMPLE,
                    TaskParticipantRole::PROFESSIONAL,
                ]),
            ],
        ];
    }
}
