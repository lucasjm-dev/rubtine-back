<?php

namespace App\Domains\Auth\Requests;

use App\Support\Users\UserProfiles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class SwitchLoginUserRequest extends FormRequest
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
            'login_as' => [
                'required',
                'string',
                Rule::in(UserProfiles::types()),
            ],
        ];
    }
}
