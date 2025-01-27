<?php

namespace Core\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
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
            'new_password' => 'required|min:8'
        ];
    }

    public function messages()
    {
        return [
            'new_password.required' => "Le nouveau mot de passe est obligatoire",
            'new_password.min' => "Le nouveau mot de passe doit contenir au minimum 8 caractères"

        ];
    }
}
