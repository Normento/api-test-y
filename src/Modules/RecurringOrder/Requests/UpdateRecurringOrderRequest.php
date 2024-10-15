<?php

namespace Core\Modules\RecurringOrder\Requests;

use Core\Modules\RecurringService\Models\RecurringService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecurringOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "service_id" => [
                'sometimes',
                "uuid",
                'distinct:strict',
                Rule::exists(RecurringService::class, 'id')
                    ->where(function ($query) {
                        return $query->whereNull('deleted_at');
                    }),
            ],
            'employee_salary' => 'sometimes|missing_with:customer_budget|integer|gt:0',
            'customer_budget' => 'sometimes|missing_with:employee_salary|integer|gt:0',
            'cnss' => ['sometimes', 'boolean'],
            'address' => 'sometimes|string',
            'is_archived' => 'sometimes|boolean',
            'archiving_reason' => 'required_if:is_archived,true|string',
            'intervention_frequency' => ['sometimes', 'integer', 'in:7,6,5,4,3,2,1'],
            'number_of_employees' => 'sometimes|integer|gt:0',
            'description' => 'sometimes|string',
        ];
    }


    public function messages(): array
    {
        return [
            'service_id.uuid' => "L'identifiant du service est invalide.",
            'service_id.distinct' => "Chaque identifiant de service doit être unique.",
            'service_id.exists' => "Le service sélectionné n'existe pas ou a été supprimé.",

            'employee_salary.missing_with' => "Le salaire de l'employé ne peut être spécifié en l'absence du budget du client.",
            'employee_salary.integer' => "Le salaire de l'employé doit être un nombre entier.",
            'employee_salary.gt' => "Le salaire de l'employé doit être supérieur à zéro.",

            'customer_budget.missing_with' => "Le budget du client ne peut être spécifié en l'absence du salaire de l'employé.",
            'customer_budget.integer' => "Le budget du client doit être un nombre entier.",
            'customer_budget.gt' => "Le budget du client doit être supérieur à zéro.",

            'cnss.boolean' => "Le champ CNSS doit être vrai ou faux.",

            'address.string' => "L'adresse doit être une chaîne de caractères.",

            'is_archived.boolean' => "Le champ d'archivage doit être vrai ou faux.",
            'archiving_reason.required_if' => "Une raison d'archivage est requise lorsque le champ d'archivage est activé.",

            'intervention_frequency.integer' => "La fréquence d'intervention doit être un nombre entier.",
            'intervention_frequency.in' => "La fréquence d'intervention doit être comprise entre 1 et 7.",

            'number_of_employees.integer' => "Le nombre d'employés doit être un nombre entier.",
            'number_of_employees.gt' => "Le nombre d'employés doit être supérieur à zéro.",

            'description.string' => "La description doit être une chaîne de caractères.",
        ];
    }
}
