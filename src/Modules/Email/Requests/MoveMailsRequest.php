<?php

namespace Core\Modules\Email\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveMailsRequest extends FormRequest
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
            'from' => 'required|string',
            'to' => 'required|string',
            'message_uid.*' => 'distinct:strict',

        ];
    }


    public function messages(): array
    {
        return [

            'from.required' => 'Le paramètre from est requis.',
            'from.string' => 'Le paramètre from doit être une chaîne de caractères.',
            'to.required' => 'Le paramètre to est requis.',
            'to.string' => 'Le paramètre to doit être une chaîne de caractères.',
            'message_uid.required' => 'Le paramètre message_uid est requis.',
            'message_uid.array' => 'Le paramètre message_uid doit être un tableau.',
            'message_uid.*.distinct' => 'Chaque identifiant de message (message_uid) doit être unique et ne pas se répéter.'


        ];
    }
}
