<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterUserRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'username' => 'nullable|string|max:255',
            'full_name' => 'nullable|string|max:255',

            'email' => [
                'required_without:user_id',
                'string',
                'email',
                Rule::unique('users', 'email')->ignore($this->user_id),
            ],

            'password' => [
                'required_without:user_id',
                'string',
                'min:6',
                'confirmed',
            ],
        ];
    }
}
