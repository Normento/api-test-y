<?php

namespace Core\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivateAccountRequest extends FormRequest
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
            'code' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => "Le code d'activation est obligatoire",
        ];
    }

}
