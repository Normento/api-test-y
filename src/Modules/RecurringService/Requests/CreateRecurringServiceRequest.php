<?php

namespace Core\Modules\RecurringService\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRecurringServiceRequest extends FormRequest
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
            'name' => ['required', 'string', Rule::unique('recurring_services')->whereNull('deleted_at')],
            'placement_fee' => 'required|integer|gte:0',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif'
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Le nom du service ylomi direct est obligatoire',
            'name.unique' => 'Ce service existe déja',
            'placement_fee.required' => 'Le Champ placement_fee est obligatoire',
            'name.string' => 'Le nom du service ylomi direct doit être une chaine de caractere',
            'image.required' => "L'image du service est obligatoire",
            'image.image' => "Veuillez sélectionner une image comme photo du service"
        ];
    }
}
