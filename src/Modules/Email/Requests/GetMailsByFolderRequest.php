<?php

namespace Core\Modules\Email\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetMailsByFolderRequest extends FormRequest
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
            'limit' => 'integer|min:1|max:100',
            'page' => 'integer|min:1',
        ];
    }


    public function messages(): array
    {
        return [
            'limit.integer' => 'Le paramètre limit doit être un entier.',
            'limit.min' => 'Le paramètre limit doit être au moins 1.',
            'limit.max' => 'Le paramètre limit ne peut pas dépasser 100.',
            'page.integer' => 'Le paramètre page doit être un entier.',
            'page.min' => 'Le paramètre page doit être au moins 1.',
        ];
    }
}
