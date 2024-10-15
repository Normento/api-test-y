<?php

namespace Core\Modules\PunctualOrder\Requests;

use Core\Modules\Professional\Models\Professional;
use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;
class StoreOffersRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'offers' => 'required|array',
            "offers.*.price" => 'required|integer|gt:0',
            "offers.*.professional_id" => 'required|uuid|distinct|exists:professionals,id',
            "offers.*.description" => ''
        ];
        
    }

    public function messages(): array
    {
        return [
            'offers.required' => "Au moins une offre est requis.",
            'offers.array' => "Les offres doivent être au format tableau.",
            'offers.*.price.required' => 'Vous devez mettre un prix',
            'offers.*.price.gt' => 'Le prix doit être supérieur à zéro',
            'offers.*.price.integer' => 'Le prix n\'est pas valid',
            'offers.*.professional_id.required' => 'Vous devez choisir un professionnel',
            'offers.*.professional_id.uuid' => 'L\'ID du professionnel doit être un UUID valide',
            'offers.*.professional_id.exists' => 'Le professionnel séléctionné n\'existe pas',
            'offers.*.professional_id.distinct' => 'Vous avez sélectionné un même professionel plus d\'une fois.Veuillez vérifier.',

        ];
    }
}
