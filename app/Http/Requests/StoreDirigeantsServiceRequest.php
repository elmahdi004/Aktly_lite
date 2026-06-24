<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDirigeantsServiceRequest extends FormRequest
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
            // 'is_not_bce' => 'required|boolean',
            // 'surname' => 'required|string|max:255',
            // 'givenName' => 'required|string|max:255',
        ];
    }
}
