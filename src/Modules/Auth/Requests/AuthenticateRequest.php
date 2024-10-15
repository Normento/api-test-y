<?php

namespace Core\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthenticateRequest extends FormRequest
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
            'password' => "required"
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.required' => "Merci de renseigner votre numéro de téléphone",
            'password.required' => "Merci de renseigner votre mot de passe"
        ];
    }
}
