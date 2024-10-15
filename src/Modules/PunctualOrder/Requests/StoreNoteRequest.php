<?php

namespace Core\Modules\PunctualOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
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
            'note' => 'required|numeric|min:1|max:5',
            'comment' => 'nullable|required_without:tags|string',
            'tags' => 'nullable|uuid|exists:tags,id',
        ];
    }

    public function messages()
    {
        return [
            'note.required' => 'La note est obligatoire.',
            'note.integer' => 'La note doit être un entier valide.',
            'note.between' => 'La note doit être comprise entre 1 et 5.',
            'comment.required_without' => 'Le commentaire est obligatoire si aucun tag n\'est fourni.',
            'comment.string' => 'Le commentaire doit être une chaîne de caractères.',
            'tags.uuid' => 'Le tag doit être un UUID valide.',
            'tags.exists' => 'Le tag n\'existe pas dans la base de données.',
        ];
    }
}
