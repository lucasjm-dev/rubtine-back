<?php

namespace App\Domains\Beneficiaries\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBeneficiaryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'sometimes|string|max:128',
            'last_name' => 'sometimes|string|max:128',
            'phone' => 'sometimes|string|max:64',
            'email' => 'sometimes|nullable|email|max:256',
            'user_id' => 'sometimes|nullable|integer|exists:users,id',
        ];
    }
}
