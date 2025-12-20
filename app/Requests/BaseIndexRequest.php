<?php

namespace App\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page'      => 'nullable|integer|min:1|max:9999',
            'per_page'  => 'nullable|integer|min:1|max:100',
            'sort_by'   => 'nullable|string|max:32',
            'sort_dir'  => 'nullable|in:asc,desc',
            'search'    => 'nullable|string|max:255',
        ];
    }
}
