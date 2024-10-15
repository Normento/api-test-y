<?php

namespace Core\Modules\Prospect\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProspectRequest extends FormRequest
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
            'is_company' => "sometimes|boolean",
            'company_name' => "missing_if:is_company,0|string|required_if:is_company,1",
            'first_name' => "missing_if:is_company,1|string|required_if:is_company,0 ",
            'last_name' => "missing_if:is_company,1|string|required_if:is_company,0",
            'address' => "sometimes|string",
            "phone_number" => "sometimes",
            "email" => [
                'sometimes',
                'email:rfc,dns',
                Rule::unique('prospects', 'email')->whereNull('deleted_at'),
            ],
            "ifu" => "sometimes|integer",
        ];
    }

    public function messages()
    {
        return [
            'ifu.integer' => "Le numéro IFU n'est valide.",
            'ifu.unique' => "L'ifu existe déjà ",
            'is_company.boolean' => "Le type de compte n'est pas valide.",
            'company_name.string' => "Le nom  de l'entreprise est une chaîne de caractère.",
            'email.email' => "L'email ne respecte pas le bon format ",
            'email.unique' => "L'email existe déjà ",
            'company_name.required_if' => "Le nom de l'entreprise est obligatoire.",
            'company_name.string' => "Le nom  de l'entreprise est une chaîne de caractère.",
            'company_name.missing_if' => "Le nom de l'entreprise n'est pas requis.",
            'first_name.required_if' => "Le prénom est obligatoire ",
            'first_name.missing_if' => "Le prénom n'est pas obligatoire ",
            'last_name.required_if' => "Le nom est obligatoire ",
            'last_name.missing_if' => "Le nom n'est obligatoire ",
        ];
    }
}
