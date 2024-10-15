<?php

namespace Core\Modules\Chat\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteMessageRequest extends FormRequest
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
            'messageId' => 'required|uuid|exists:messages,id',
        ];
    }


    public function messages(): array
    {
        return [
            'messageId.uuid' => "L'id du message doit etre un uuid",
            'messageId.required' => "L'id du message est obligatoire",
            'messageId.exists' => "L'id du message n'esxist pas",
        ];
    }
}
