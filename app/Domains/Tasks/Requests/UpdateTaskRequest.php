<?php

namespace App\Domains\Tasks\Requests;

use App\Domains\Tasks\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'subcategory_id' => 'sometimes|integer|exists:subcategories,id',
            'title' => 'sometimes|string|max:64',
            'description' => 'sometimes|nullable|string|max:255',
            'public' => 'sometimes|boolean',
            'status' => [
                'sometimes',
                'string',
                Rule::in(TaskStatus::values()),
            ],
        ];
    }
}
