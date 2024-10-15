<?php

namespace Core\Modules\Blog\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
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
                'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Le titre est obligatoire',
            'title.string' => 'Le title doit être un string',
            'content.required' => 'Le content est obligatoire',
            'content.string' => 'Le content doit être un text',
            'image.required' => 'L\'image est obligatoire',
            'image.image' => 'L\'image doit être une image',

        ];
    }
}
