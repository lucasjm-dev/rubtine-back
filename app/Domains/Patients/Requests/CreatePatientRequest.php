<?php

namespace App\Domains\Patients\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePatientRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:128',
            'last_name' => 'required|string|max:128',
            'phone' => 'required|string|max:64',
            'email' => 'nullable|email|max:256',
            'user_id' => 'nullable|integer|exists:users,id',
        ];
    }
}
