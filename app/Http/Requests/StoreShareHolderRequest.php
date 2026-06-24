<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShareHolderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 'demande_id' => 'required|integer',
            // 'shareholders' => 'required|array',
            // 'shareholders.*.full_name' => 'required|string|max:255',
            // 'shareholders.*.shares' => '|string|max:255',
            // 'is_origin' => 'boolean',
            // 'total_shares' => 'required|integer',
        ];
    }
}
