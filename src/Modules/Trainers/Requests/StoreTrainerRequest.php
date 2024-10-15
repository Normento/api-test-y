<?php


namespace Core\Modules\Trainers\Requests;

use Core\Modules\RecurringService\Models\RecurringService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class StoreTrainerRequest extends FormRequest
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
            'full_name' => 'required|string',
            'hourly_rate' => 'required|integer',
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'id_card' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'phone_number' => ['required',
                Rule::unique('trainers', 'phone_number')->whereNull('deleted_at'),
            ],
            'services' => 'required|array',
            "services.*.id" => [Rule::exists(RecurringService::class, 'id')->where(function ($query) {
                return $query->where('deleted_at', null);
            }), 'required', 'distinct:strict', "uuid"],
            "services.*.skill" => 'required|string',
            'services.*.years_of_experience' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'full_name.required' => "Le nom du formateur  est requis.",
            'phone_number.required' => "Le numéro de téléphone du formateur  est requis.",
            'phone_number.unique' => "Le numéro de téléphone du formateur  est déjà utilisé.",
            'hourly_rate.required' => "Le taux horaire du formateur  est requis.",
            'photo.required' => "La photo du formateur  est requis.",
            'id_card.required' => "La carte d'identité du formateur  est requis.",
            'id_card.image' => "La carte d'identité du formateur  est invalide.",
            'photo.image' => "La photo du formateur  est invalide.",
            'hourly_rate.integer' => "Le taux horaire du formateur  est invalide.",
            'services.required' => "Au moins un service est requis.",
            'services.array' => "Les services doivent être au format tableau.",
            'services.*.id.exists' => "L'ID du service n'existe pas ou a été supprimé.",
            'services.*.id.required' => "L'ID du service est requis.",
            'services.*.id.distinct' => "Les ID des services ne doivent pas être en double.",
            'services.*.id.uuid' => "L'ID du service doit être un UUID valide.",
            'services.*.skill.required' => "Le résumé de la compétence du formateur sur le service est requise.",
            'services.*.skill.string' => "Le résumé de la compétence du formateur sur le  service doit être une chaîne de caractères.",
            'services.*.years_of_experience.required' => "L'année d'expérience du formateur sur le service est requise.",
            'services.*.years_of_experience.string' => "L'année d'expérience du formateur sur le  service doit être une chaîne de caractères.",
        ];
    }
}
