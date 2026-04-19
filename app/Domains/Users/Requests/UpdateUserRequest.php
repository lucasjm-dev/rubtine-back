<?php

namespace App\Domains\Users\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
        // don't return error if email exists with same user
        $userId = auth()->id();

        return [
            'username' => 'nullable|string|max:32',
            'full_name' => 'nullable|string|max:32',
            'email'    => "nullable|email|unique:users,email,{$userId}",
            'password' => 'nullable|string|min:6|max:12|confirmed'
        ];
    }

    /**
     * Get the validated data from the request, excluding null values.
     *
     * @return array
     */
    public function validated()
    {
        return array_filter(parent::validated(), function ($value) {
            return !is_null($value);
        });
    }
}
