<?php

namespace App\Domains\Users\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProfessionalUserRequest extends FormRequest
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
            'user_id' => 'nullable|integer|min:1|max:999999999',
            'birthday' => 'nullable|date_format:Y-m-d|before:-13 years|after:-120 years',
            'about_me' => 'nullable|string|max:255',
            'subcategory_id' => 'required|integer|exists:subcategories,id'
            // 'profile_photo' => 'nullable|string|max:255',
        ];
    }
}
