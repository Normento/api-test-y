<?php

namespace Core\Modules\Auth\Requests;

use Core\Modules\Access\Models\Role;
use Core\Modules\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
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
            'profile_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'id_card' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'ifu' => 'sometimes|numeric',
            "role_id" => ['sometimes', "uuid",
                Rule::exists(Role::class, 'id')
            ],
            "invited_by" => ['required_with:role_id', "uuid",
                Rule::exists(User::class, 'id')
            ],
            'is_company' => 'sometimes|boolean',
            'company_name' => 'missing_if:is_company,false|string|required_if:is_company,true',
            'company_address' => 'missing_if:is_company,false|string|required_if:is_company,true',
            'phone_number' => ['required', Rule::unique('users')->whereNull('deleted_at')],
            'email' => ['required', Rule::unique('users')->whereNull('deleted_at'), 'email:rfc,dns'],
            'password' => ['required', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'last_name.required' => 'Le nom est obligatoire',
            'is_company.required' => 'Le type de compte est obligatoire',
            'is_company.boolean' => 'Le type de compte est un boléen',
            'company_address.string' => "L'adresse de l'entreprise est une chaîne de caractère",
            'ifu.string' => "Le numéro IFU de l'entreprise est une chaîne de caractère",
            'ifu.present' => "Le numéro IFU de l'entreprise doit être présent",
            'company_name.string' => "Le nom  de l'entreprise est une chaîne de caractère",
            'company_name.required_if' => "Le nom de l'entreprise est obligatoire quand votre compte est un compte d'entreprise",
            'company_address.required_if' => "L'adresse  de l'entreprise est obligatoire quand votre compte est un compte d'entreprise",

            'company_name.missing_if' => "Le nom de l'entreprise n'est pas requis quand votre compte n'est pas un compte d'entreprise",
            'company_address.missing_if' => "L'adresse  de l'entreprise n'est pas  quand votre compte n'est pas un compte d'entreprise",
            'first_name.required' => 'Le prénom est obligatoire',
            'email.required' => 'L\'email est obligatoire',

            'profile_image.image' => 'La photo de profile doit être une image valide de 2M de taille au plus',

            'password.required' => 'Le mot de passe est obligatoire',
            'phone_number.required' => 'Le numéro de téléphone est obligatoire',
            'email.email' => 'L\'email ne respecte pas le bon format',
            'email.unique' => 'L\'email est déja utilisé par un utilisateur',
            'phone_number.unique' => 'Le numéro de téléphone est déja utilisé par un utilisateur',

        ];
    }
}
