<?php

namespace App\Domains\Users\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCompanyUserRequest extends FormRequest
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
            'birth_date' => 'nullable|date_format:Y-m-d|before:-13 years|after:-120 years',
            'about_me' => 'nullable|string|max:255',
            'tax_id' => 'required|integer|digits:11',
            'business_name' => 'required|string|max:32',
            // 'profile_photo' => 'nullable|string|max:255',
        ];
    }
}
