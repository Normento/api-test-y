<?php

namespace Core\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyCodeRequest extends FormRequest
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

    public function messages()
    {
        return [
            'code.required' => "Le code à vérifier est obligatoire",
        ];
    }

}
