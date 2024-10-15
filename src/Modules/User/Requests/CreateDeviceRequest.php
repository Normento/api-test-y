<?php

namespace Core\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateDeviceRequest extends FormRequest
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
    public function rules()
    {
            return [
                'token' => 'required|string',
            ];

    }

    public function messages()
    {
        return [
            'token.required' => "Le token  est obligatoire pour enregistrer ce device",
        ];
    }
}
