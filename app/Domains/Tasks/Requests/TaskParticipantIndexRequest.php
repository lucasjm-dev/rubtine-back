<?php

namespace App\Domains\Tasks\Requests;

use App\Domains\Tasks\Enums\TaskParticipantStatus;
use App\Requests\BaseIndexRequest;
use Illuminate\Validation\Rule;

class TaskParticipantIndexRequest extends BaseIndexRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'status' => [
                'nullable',
                'string',
                'max:32',
                Rule::in(TaskParticipantStatus::values()),
            ],
        ]);
    }
}
