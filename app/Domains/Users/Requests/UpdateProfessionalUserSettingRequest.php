<?php

namespace App\Domains\Users\Requests;

use App\Domains\Tasks\Requests\Concerns\ValidatesCancellationPolicy;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfessionalUserSettingRequest extends FormRequest
{
    use ValidatesCancellationPolicy;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'session_duration_minutes' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1440'],
        ], $this->cancellationPolicyRules());
    }
}
