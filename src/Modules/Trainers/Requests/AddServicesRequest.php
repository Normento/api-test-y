<?php


namespace Core\Modules\Trainers\Requests;

use Core\Modules\RecurringService\Models\RecurringService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class AddServicesRequest extends FormRequest
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

            'services' => 'required|array',
            "services.*.id" => [Rule::exists(RecurringService::class, 'id')->where(function ($query) {
                return $query->where('deleted_at', null);
            }), 'required', 'distinct:strict', "uuid",
                Rule::unique('trainer_recurring_service', 'recurring_service_id')
                    ->where(function ($query) {
                        return  $query->where('trainer_id', $this->trainer->id)->where('deleted_at', null);
                    })
                ],
            "services.*.skill" => 'required|string',
            'services.*.years_of_experience' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'services.required' => "Au moins un service est requis.",
            'services.array' => "Les services doivent être au format tableau.",
            'services.*.id.exists' => "L'ID du service n'existe pas ou a été supprimé.",
            'services.*.id.required' => "L'ID du service est requis.",
            'services.*.id.unique' => "Veuillez sélectionner les services dans lesquels ce formateur ne forme pas",
            'services.*.id.distinct' => "Les ID des services ne doivent pas être en double.",
            'services.*.id.uuid' => "L'ID du service doit être un UUID valide.",
            'services.*.skill.required' => "Le résumé de la compétence du formateur sur le service est requise.",
            'services.*.skill.string' => "Le résumé de la compétence du formateur sur le  service doit être une chaîne de caractères.",
            'services.*.years_of_experience.required' => "L'année d'expérience du formateur sur le service est requise.",
            'services.*.years_of_experience.string' => "L'année d'expérience du formateur sur le  service doit être une chaîne de caractères.",
        ];
    }
}
