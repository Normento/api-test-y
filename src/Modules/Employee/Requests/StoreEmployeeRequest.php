<?php

namespace Core\Modules\Employee\Requests;

use Core\Modules\FocalPoints\Models\FocalPoint;
use Core\Modules\Partners\Models\Partner;
use Core\Modules\RecurringService\Models\RecurringService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;


class StoreEmployeeRequest extends FormRequest
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
        $rules = [
            'full_name' => 'required|string',
            'address' => 'required|string',
            'birthday' => 'required|date|before:today',
            'marital_status' => 'required|string',
            'phone_number' => ['required',
                Rule::unique('employees', 'phone_number')->whereNull('deleted_at'),
            ],
            'mtn_number' => [Rule::requiredIf(function () {
                return !Auth::guest() && ($this->input('type') == 0 || $this->input('type') == 2);
            }),
                Rule::unique('employees', 'mtn_number')->whereNull('deleted_at'),
            ],
            'flooz_number' => [
                Rule::requiredIf(function () {
                    return !Auth::guest() && ($this->input('type') == 0 || $this->input('type') == 2);
                }
                ),
                Rule::unique('employees', 'flooz_number')->whereNull('deleted_at'),
            ],
            'ifu' => ['sometimes',
                Rule::unique('employees', 'ifu')->whereNull('deleted_at'),
            ],
            'nationality' => 'required|string',
            'profile_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'degree' => 'required|string',
            'services' => 'required|array',
            "services.*.id" => ['required', "uuid", 'distinct:strict', Rule::exists(RecurringService::class, 'id')->where(function ($query) {
                return $query->where('deleted_at', null);
            })],
            "services.*.about" => 'required|string',
            'services.*.years_of_experience' => 'required|string',
            'services.*.salary_expectation' => 'required|integer|gt:0',

            'pictures' => [Rule::requiredIf(function () {
                return !Auth::guest() && ($this->input('type') == 0 || $this->input('type') == 2 || $this->input('type') == 5);
            }), 'array', 'size:2'],

            'pictures.*' => "image|mimes:jpeg,png,jpg|max:2048",

            'proof_files' => [

                Rule::requiredIf(function () {
                    return !Auth::guest() && ($this->input('type') == 0 || $this->input('type') == 2 || $this->input('type') == 5);
                }),
                'array'
            ],

            'proof_files.*' => "image|mimes:jpeg,png,jpg|max:2048",

            "partner_id" => ['missing_with:focal_point_id', "uuid", Rule::exists(Partner::class, 'id')->where(function ($query) {
                return $query->where('deleted_at', null);
            })],

            "focal_point_id" => ['missing_with:partner_id', "uuid", Rule::exists(FocalPoint::class, 'id')->where(function ($query) {
                return $query->where('deleted_at', null);
            })],

            'type' => ['required', 'integer', Rule::in(!Auth::guest() ? [0, 2, 4, 5] : 1)],
        ];
        !Auth::guest() ? $rules['proof_files'] = 'size:2' : null;

        return $rules;
    }

    public function messages(): array
    {
        return [
            'pictures.required' => "Les photos de l'employée sont obligatoire",
            'pictures.array' => "Les photos de l'employé doivent être au format tableau.",
            'proof_files.array' => "Les pièces justificatves  de l'employé doivent être au format tableau.",
            'pictures.size' => "Merci de sélectionner  deux photos  pertinentes à montrer aux clients",
            'proof_files.required' => "Les pièces justificatves de l'employée sont obligatoire",
            'proof_files.size' => "Merci de sélectionner maximun deux pièces justificatves ",
            'profile_image.required' => "La photo de profile est obligatoire",
            'profile_image.image' => "La photo de profile est invalide",
            'pictures.*.image' => "Merci de sélectionner des images comme photos de l'employé ",
            'pictures.*.mimes' => "Merci de sélectionner des images comme photos de l'employé ",
            'proof_files.*.image' => "Merci de sélectionner des images comme pièces justificatves de l'employé ",
            'profile_image.mimes' => "La photo de profile est invalide",
            'profile_image.uploaded' => "La photo de profile est trop lourde",

            'focal_point_id.uuid' => "Id du point focal invalide",
            'partner_id.uuid' => "Id du partenaire  invalide",
            'partner_id.missing_with' => "Id du partenaire et l'Id du point focal ne peuvent pas être tous deux renseigné",
            'focal_point_id.missing_with' => "Id du partenaire et l'Id du point focal ne peuvent pas être tous deux renseigné",
            'partner_id.exists' => "Id du partenaire invalide",
            'focal_point_id.exists' => "Id du point focal invalide",

            'full_name.required' => "Le nom complet est obligatoire",
            'address.required' => "L'adresse est obligatoire",
            'phone_number.required' => "Le numéro de téléphone est obligatoire",
            'mtn_number.required' => "Le numéro MTN MoMo est obligatoire",
            'flooz_number.required' => "Le numéro FLOOZ  est obligatoire",
            'phone_number.unique' => 'Ce numéro de téléphone est déja utilisé par un autre employée',
            'ifu.unique' => 'Le numéro IFU est déjà utilisé',
            'mobile_money_number.required' => "Le numéro de compte mobile money est obligatoire",
            'mobile_money_number.unique' => 'Ce numéro de compte mobile money est déja utilisé par un autre employée',
            'birthday.required' => 'La date de naissance de l\'employée est obligatoire',
            'birthday.date' => 'La date de naissance de l\'employée est invalide',
            'birthday.before' => 'La date de naissance de l\'employée est invalide',
            'marital_status' => "La status marital est obligatoire",
            'degree.required' => "Le diplôme est obligatoire",
            'nationality.required' => "La nationalité est obligatoire",

            'services.required' => "Au moins un service est requis.",
            'services.array' => "Les services doivent être au format tableau.",
            'services.*.id.exists' => "L'ID du service n'existe pas ou a été supprimé.",
            'services.*.id.required' => "L'ID du service est requis.",
            'services.*.id.distinct' => "Les ID des services ne doivent pas être en double.",
            'services.*.id.uuid' => "L'ID du service doit être un UUID valide.",
            'services.*.about.required' => "Le résumé de la compétence de l'employé sur le service est requis.",
            'services.*.about.string' => "Le résumé de la compétence de l'employé sur le  service doit être une chaîne de caractères.",
            'services.*.years_of_experience.required' => "L'année d'expérience de l'employé sur le service est requise.",
            'services.*.years_of_experience.string' => "L'année d'expérience de l'employé sur le  service doit être une chaîne de caractères.",

            'services.*.salary_expectation.required' => "La prétention salariale sur le service est requise.",

            'services.*.salary_expectation.integer' => "La prétention salariale est invalide.",

            'type.in' => !Auth::guest() ? "Le type d'employé accepte uniquement 0, 2 , 4, 5' comme valeur" : "Le type d'employé accepte uniquement 1 comme valeur",

            'type.integer' => "Le type d'employé est invalide",

            'type.required' => "Le type d'employé est obligatoire",

            'mtn_number.unique' => 'Ce numéro de compte MTN mobile money est déja utilisé par un autre employée',

            'flooz_number.unique' => 'Ce numéro de compte MOOV mobile money est déja utilisé par un autre employée',

        ];
    }
}
