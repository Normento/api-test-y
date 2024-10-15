<?php

namespace Core\Modules\Employee\Requests;

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
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'services' => 'required|array',
            "services.*.id" => ['required', "uuid",
                'distinct:strict',
                Rule::exists(RecurringService::class, 'id')
                    ->where(function ($query) {
                        return $query->whereNull('deleted_at');
                    }),
                Rule::unique('employee_recurring_service', 'recurring_service_id')
                    ->where(function ($query) {
                        return $query->where('employee_id', $this->employee->id)
                            ->whereNull('deleted_at');
                    })
            ],
            "services.*.about" => 'required|string',
            'services.*.years_of_experience' => 'required|string',
            'services.*.salary_expectation' => 'required|integer',];
    }

    public function messages(): array
    {
        return [

            'services.required' => "Au moins un service est requis.",
            'services.array' => "Les services doivent être au format tableau.",
            'services.*.id.exists' => "L'ID du service n'existe pas ou a été supprimé.",
            'services.*.id.unique' => "L'employé fournit déjà l'un des services sélectionnés ",

            'services.*.id.required' => "L'ID du service est requis.",
            'services.*.id.distinct' => "Les ID des services ne doivent pas être en double.",
            'services.*.id.uuid' => "L'ID du service doit être un UUID valide.",
            'services.*.about.required' => "Le résumé de la compétence de l'employé sur le service est requis.",
            'services.*.about.string' => "Le résumé de la compétence de l'employé sur le  service doit être une chaîne de caractères.",
            'services.*.years_of_experience.required' => "L'année d'expérience de l'employé sur le service est requise.",
            'services.*.years_of_experience.string' => "L'année d'expérience de l'employé sur le  service doit être une chaîne de caractères.",

            'services.*.salary_expectation.required' => "La prétention salariale sur le service est requise.",
            'services.*.salary_expectation.integer' => "La prétention salariale est invalide.",

        ];
    }


}
