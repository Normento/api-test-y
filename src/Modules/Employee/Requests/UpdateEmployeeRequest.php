<?php

namespace Core\Modules\Employee\Requests;

use Core\Modules\FocalPoints\Models\FocalPoint;
use Core\Modules\Partners\Models\Partner;
use Core\Modules\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
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

    public function rules(): array
    {
        return [
            'full_name' => 'sometimes|string',
            'address' => 'sometimes|string',
            'birthday' => 'sometimes|date|before:today',
            'marital_status' => 'sometimes|string',
            'phone_number' => ['sometimes',
                Rule::unique('employees', 'phone_number')
                    ->ignore($this->employee->id)
                    ->whereNull('deleted_at'),
            ],
            'mtn_number' => [
                'sometimes',
                Rule::unique('employees', 'mtn_number')
                    ->ignore($this->employee->id)
                    ->whereNull('deleted_at'),
            ],
            'flooz_number' => ['sometimes',
                Rule::unique('employees', 'flooz_number')
                    ->ignore($this->employee->id)
                    ->whereNull('deleted_at'),
            ],
            'ifu' => ['sometimes',
                Rule::unique('employees', 'ifu')
                    ->ignore($this->employee->id)
                    ->whereNull('deleted_at'),
            ],
            'nationality' => 'sometimes|string',
            'profile_image' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            'degree' => 'sometimes|string',

            'pictures' => ['sometimes', 'array', 'size:2'],

            'pictures.*' => "image|mimes:jpeg,png,jpg|max:2048",


            'proof_files' => [
                'sometimes',
                'array', 'size:2'
            ],

            'proof_files.*' => "image|mimes:jpeg,png,jpg|max:2048",

            "partner_id" => ['missing_with:focal_point_id', "uuid", Rule::exists(Partner::class, 'id')->where(function ($query) {
                return $query->where('deleted_at', null);
            })],

            "focal_point_id" => ['missing_with:partner_id', "uuid", Rule::exists(FocalPoint::class, 'id')->where(function ($query) {
                return $query->where('deleted_at', null);
            })],

            'status' => ['sometimes', 'integer', Rule::in([-1, 1])],

            'type' => ['sometimes', 'integer', Rule::in([0, 2, 3, 4, 5])],


            "co_id" => ['sometimes', "uuid", Rule::exists(User::class, 'id')
            ],

            'is_share' => ['sometimes', 'boolean'],

            'share_observation' => 'sometimes',
        ];
    }

    public function messages(): array
    {
        return [
            'pictures.size' => "Merci de sélectionner maximun deux photos  pertinentes à montrer au client",
            'proof_files.size' => "Merci de sélectionner maximun deux pièces justificatves ",
            'profile_image.image' => "La photo de profile est invalide",
            'pictures.*.image' => "Merci de sélectionner des images comme photos de l'employé ",
            'proof_files.*.image' => "Merci de sélectionner des images comme pièces justificatves de l'employé ",
            'profile_image.mimes' => "La photo de profile est invalide",
            'profile_image.uploaded' => "La photo de profile est trop lourde",
            'focal_point_id.uuid' => "Id du point focal invalide",
            'co_id.uuid' => "Id du CO est invalide",
            'partner_id.uuid' => "Id du partenaire  invalide",
            'partner_id.exists' => "Id du partenaire invalide",
            'focal_point_id.exists' => "Id du point focal invalide",
            'co_id.exists' => "Id du CO est invalide",
            'phone_number.unique' => 'Ce numéro de téléphone est déja utilisé par un autre employée',
            'mobile_money_number.unique' => 'Ce numéro de compte mobile money est déja utilisé par un autre employée',
            'birthday.date' => 'La date de naissance de l\'employée est invalide',
            'birthday.before' => 'La date de naissance de l\'employée est invalide',
            'marital_status' => "La status marital est obligatoire",

            'status.integer' => "Le status de l'employé doit être au format entier",

            'type.integer' => "Le type d'employé est invalide",

            'status.in' => "Le status de l'employé accepte uniquement -1 , 1",

            'salary.required_if' => "Le salaire  est obligatoire",
            'salary.integer' => "Le salaire  est invalide",
            'salary.gt' => "Le salaire  est invalide",

            'contract.required_if' => "Le contrat  est obligatoire",
            'contract.mimes' => "Le contrat  est invalide",

            'is_share.boolean' => "Mentionnez 0 ou 1 pour renseigner si l'employé doit être visible par les autres",

            'mtn_number.unique' => 'Ce numéro de compte MTN mobile money est déja utilisé par un autre employée',

            'flooz_number.unique' => 'Ce numéro de compte MOOV mobile money est déja utilisé par un autre employée',

        ];
    }

}
