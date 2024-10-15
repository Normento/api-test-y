<?php

namespace Core\Modules\Email\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkMessageAsRequest extends FormRequest
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
            'message_uid' => 'required|array',
             'folder' => 'required|string',
             'seen' => 'required|boolean',
             'message_uid.*' => 'distinct:strict',
        ];
    }



    public function messages(): array
    {
        return [
            'folder.required' => 'Le paramètre folder est requis.',
            'folder.string' => 'Le paramètre folder doit être une chaîne de caractères.',
            'message_uid.required' => 'Le paramètre message_uid est requis.',
            'message_uid.array' => 'Le paramètre message_uid doit être un tableau.',
            'seen.required' => 'Le paramètre seen est requis.',
            'seen.boolean' => 'Le paramètre seen doit être un booléen (true ou false).',
            'message_uid.*.distinct' => 'Chaque identifiant de message (message_uid) doit être unique et ne pas se répéter.'

        ];
    }
}
