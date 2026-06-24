<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressServiceRequest extends FormRequest
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
            'an_address' => 'required|string',
            'demande_id' => 'required|integer',
            'rue' => 'required|string|max:255',
            'numero' => 'required|string|max:255',
            'box' => 'nullable|string|max:255',
            'zip_code' => 'required|string|max:255',
            'localite' => 'required|string|max:255',
            'pays' => 'required|string|max:255',
        ];
    }
}
