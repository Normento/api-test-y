<?php

namespace Core\Modules\RecurringService\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecurringServiceRequest extends FormRequest
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
    public function rules()
    {
        return [
            'name' => ['sometimes', 'string', Rule::unique('recurring_services')
                ->ignore($this->service->id)
                ->withoutTrashed('deleted_at')],
            'placement_fee' => 'sometimes|integer|gte:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'is_archived' => "sometimes|boolean",
            'is_highlighted' => "sometimes|boolean"
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Le nom du service ylomi direct est obligatoire',
            'name.unique' => 'Ce service récurrent existe déja',
            'placement_fee.required' => 'Le Champ placement_fee est obligatoire',
            'name.string' => 'Le nom du service ylomi direct doit être une chaine de caractere',
            'image.required' => "L'image du service est obligatoire",
            'image.image' => "Veuillez sélectionner une image comme photo du service"
        ];
    }
}
