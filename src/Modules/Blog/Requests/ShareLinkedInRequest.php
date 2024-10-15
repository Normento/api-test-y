<?php

namespace Core\Modules\Blog\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShareLinkedInRequest extends FormRequest
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
                'title' => 'required|string',
                'content' => 'required|string',
                'post_image' => 'required|string',
                'post_id' => 'required|string',
            ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Le titre est obligatoire',
            'title.string' => 'Le title doit être un string',
            'content.required' => 'Le content est obligatoire',
            'content.string' => 'Le content doit être un text',
            'post_image.required' => 'L\'image est obligatoire',
            'post_image.string' => 'L\'image doit être une chaine de caractere',
            'post_id.required' => 'L\'id est obligatoire',
            'post_id.string' => 'L\'id doit être une chaine de caractere',

        ];
    }
}
