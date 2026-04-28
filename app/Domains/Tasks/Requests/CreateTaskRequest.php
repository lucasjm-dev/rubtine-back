<?php

namespace App\Domains\Tasks\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTaskRequest extends FormRequest
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
            'subcategory_id' => 'nullable|integer|exists:subcategories,id',
            'beneficiary_id' => 'nullable|integer|exists:beneficiaries,id',
            'title' => 'required|string|max:64',
            'description' => 'nullable|string|max:255',
            'public' => 'nullable|boolean',
            // 'status' => 'required|boolean', By default always DRAFT 
        ];
    }
}
