<?php

namespace Core\Modules\Blog\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostviewsRequest extends FormRequest
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
                'fingerprint' => 'required|string',
                'post_id' => 'required|string',
            ];
    }

    public function messages(): array
    {
        return [
            'fingerprint.required' => 'Fingerprint requis',
            'fingerprint.string' => 'Fingerprint en chaine de caractere',
            'post_id.string' => 'Le post est requis',
            'post_id.required' => 'Id du post requis'

        ];
    }
}
