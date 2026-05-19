<?php

namespace App\Domains\Tasks\Requests;

use App\Requests\BaseIndexRequest;

class TaskEventIndexRequest extends BaseIndexRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), []);
    }
}
