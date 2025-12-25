<?php

namespace App\Domains\Tasks\Requests;

use App\Domains\Tasks\Enums\TaskRequestStatus;
use App\Requests\BaseIndexRequest;
use Illuminate\Validation\Rule;

class TaskRequestIndexRequest extends BaseIndexRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'status' => [
                'sometimes',
                'string',
                Rule::in(TaskRequestStatus::values()),
            ],
        ]);
    }
}
