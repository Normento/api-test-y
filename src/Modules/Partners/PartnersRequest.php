<?php


namespace Core\Modules\Partners;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class PartnersRequest extends FormRequest
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

        return $this->isMethod('POST') ? [
            'name' => 'required|string',
            "email" => ['required', 'email:rfc,dns',
                Rule::unique('partners', 'email')->whereNull('deleted_at'),
            ],
            'percentage' => 'required|integer|gt:0',
        ] : [
            'name' => 'sometimes|string',
            "email" => ['sometimes', 'email:rfc,dns',
                Rule::unique('partners', 'email')->whereNull('deleted_at'),
            ],
            'percentage' => 'sometimes|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => "Le nom du partenaire  est requis.",
            'email.required' => "L'email  du partenaire  est requis.",
            'email.unique' => "L'email  du partenaire  est déjà utilisé.",
            'email.email' => "L'email  du partenaire  est déjà invalide.",
            'percentage.required' => "Le pourcentage du partenaire  est requis.",
            'percentage.integer' => "Le pourcentage du partenaire  est invalide.",
            'percentage.gt' => "Le pourcentage du partenaire  est invalide.",
        ];
    }
}
