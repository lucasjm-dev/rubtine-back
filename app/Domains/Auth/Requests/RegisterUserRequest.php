<?php

namespace App\Domains\Auth\Requests;

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
            'username' => 'nullable|string|max:32',
            'full_name' => 'nullable|string|max:32',

            'email' => [
                'required',
                'string',
                'email',
                Rule::unique('users', 'email'),
            ],

            'password' => [
                'required',
                'string',
                'min:6',
                'max:12',
                'confirmed',
            ],
        ];
    }
}
