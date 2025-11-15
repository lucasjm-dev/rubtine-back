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
            'email'    => "required|string|email|unique:users,email,{$userId}",
            'password' => 'required|string|min:6|max:12'
        ];
    }
}
