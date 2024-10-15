<?php

namespace Core\Modules\Prospect\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterProspectRequest extends FormRequest
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
            'is_company' => "required|boolean ",
            'company_name' => "missing_if:is_company,0|string|required_if:is_company,1",
            'first_name' => "missing_if:is_company,1|string|required_if:is_company,0 ",
            'last_name' => "missing_if:is_company,1|string|required_if:is_company,0",
            'address' => "required|string ",
            "phone_number" => "sometimes|string",
            "email" => [
                'required_without:phone_number',
                'email:rfc,dns',
                Rule::unique('prospects', 'email')->whereNull('deleted_at'),
            ],
            "ifu" => "required|integer|unique:prospects,ifu",
        ];
    }

    public function messages()
    {
        return [
            'is_company.required' => "Le type de compte est obligatoire.",
            'is_company.boolean' => "Le type de compte n'est pas valide.",
            'address.required' => "L'adresse de l'entreprise est obligatoire.",
            'ifu.required' => "Le numéro IFU est obligatoire.",
            'ifu.integer' => "Le numéro IFU n'est valide.",
            'ifu.unique' => "Le numéro IFU existe déjà",
            'company_name.required_if' => "Le nom de l'entreprise est obligatoire.",
            'company_name.string' => "Le nom  de l'entreprise est une chaîne de caractère.",
            'company_name.missing_if' => "Le nom de l'entreprise n'est pas requis.",
            'first_name.required_if' => "Le prénom est obligatoire ",
            'first_name.missing_if' => "Le prénom n'est pas obligatoire ",
            'last_name.required_if' => "Le nom est obligatoire ",
            'last_name.missing_if' => "Le nom n'est obligatoire ",
            'email.required_without' => "L'email est obligatoire ",
            'email.email' => "L'email ne respecte pas le bon format ",
            'email.unique' => "L'email existe déjà ",
        ];
    }
}
