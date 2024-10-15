<?php

namespace Core\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
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
            'old_password' => 'required',
            'new_password' => 'required|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'old_password.required' => "L'ancien mot de passe est obligatoire",
            'new_password.required' => "Le nouveau mot de passe est obligatoire",
        ];
    }
}
