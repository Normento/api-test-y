<?php

namespace Core\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForgetPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'phone_number' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.required' => "Le numéro de téléphone est obligatoire"
        ];
    }
}
