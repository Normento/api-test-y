<?php

namespace Core\Modules\PunctualOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreOffersRequest extends FormRequest
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
            "price" => 'sometimes|integer|gt:0',
            "professional_id" =>'sometimes|uuid|exists:professionals,id',
            "status" => 'sometimes|integer|between:1,2',
            "negotiation" => 'sometimes|string',
            "description" => 'nullable|string'
        ];
    }

    public function messages(): array
    {
        return [
            'price.gt' => 'Le prix doit être supérieur à zéro',
            'price.integer' => 'Le prix n\'est pas valid',
            'status.integer' => 'Le status n\'est pas valid',
            'status.between' => 'Le status n\'est pas valid',
            'negotiation.string' => 'Veuillez remplir le champ',
            'professional_id.uuid' => 'Le format du professionnel séléctionné est invalid',
            'professional_id.exists' => 'Le professionnel séléctionné n\'existe pas',
        ];
    }
}
