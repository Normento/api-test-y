<?php

namespace Core\Modules\Email\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetMailDetailsRequest extends FormRequest
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
            'folder' => 'required|string',
            'message_uid' => 'required|integer',
        ];
    }


    public function messages(): array
    {
        return [
            'folder.required' => 'Le paramètre folder est requis.',
            'folder.string' => 'Le paramètre folder doit être une chaîne de caractères.',
            'message_uid.required' => 'Le paramètre message_uid est requis.',
            'message_uid.integer' => 'Le paramètre message_uid doit être un entier.',
        ];
    }
}
