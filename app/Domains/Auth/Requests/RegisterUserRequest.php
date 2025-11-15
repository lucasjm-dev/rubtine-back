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
                'required_without:user_id',
                'string',
                'email',
                Rule::unique('users', 'email')->ignore($this->user_id),
            ],

            'password' => [
                'required_without:user_id',
                'string',
                'min:6',
                'max:12',
                'confirmed',
            ],
        ];
    }

    protected function prepareForValidation()
    {
        if (!is_numeric($this->user_id)) {
            $this->merge(['user_id' => null]);
            return;
        }

        $value = (int) $this->user_id;

        if ($value < 1 || $value > 999999999) {
            $this->merge(['user_id' => null]);
            return;
        }

        $this->merge(['user_id' => $value]);
    }
}
