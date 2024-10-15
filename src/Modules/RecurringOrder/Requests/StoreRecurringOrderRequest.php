<?php

namespace Core\Modules\RecurringOrder\Requests;

use Core\Modules\RecurringService\Models\RecurringService;
use Core\Modules\User\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecurringOrderRequest extends FormRequest
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
     *
     */
    public function rules(): array
    {
        return [
            'orders' => 'required|array',
            'type' => 'required|integer|in:1,2,3',
            'user_id' => [
                'sometimes',
                'uuid',
                Rule::exists(User::class, 'id')
                    ->where(function ($query) {
                        return $query->whereNull('deleted_at');
                    })
            ],
            "orders.*.service_id" => [
                'required',
                "uuid",
                'distinct:strict',
                Rule::exists(RecurringService::class, 'id')
                    ->where(function ($query) {
                        return $query->whereNull('deleted_at');
                    }),
            ],
            'orders.*.employee_salary' => 'missing_with:orders.*.customer_budget|integer|gt:0',
            'orders.*.customer_budget' => 'missing_with:orders.*.employee_salary|integer|gt:0',
            'orders.*.cnss' => [Rule::requiredIf(function () {
                return ($this->input('type') == 1);
            }), 'boolean'],
            'orders.*.address' => 'required|string',
            'orders.*.intervention_frequency' => [Rule::requiredIf(function () {
                return ($this->input('type') == 1);
            }),  'integer', 'in:7,6,5,4,3,2,1'],
            'orders.*.number_of_employees' => 'required|integer|gt:0',
            'orders.*.description' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'orders.required' => 'Les commandes sont obligatoires',
            'type.required' => 'Le type des commandes est obligatoires',
            'type.integer' => 'Le type des commandes est invalide',
            'type.in' => 'Le type des commandes est invalide',
            'orders.*.employee_salary.missing_with' => 'Merci de mentionner soit le salaire de l\'employé, soit le budget global',
            'orders.*.customer_budget.missing_with' => 'Merci de mentionner soit le salaire de l\'employé, soit le budget global',
            'orders.*.intervention_frequency.required_if' => 'La fréquence d\'intervention de l\'employé de la commande numéro :position de votre tableau est obligatoires si le type de la commande est 1 ',
            'orders.*.employee_salary.integer' => 'Le salaire de l\'employé de la commande numéro :position de votre tableau est invalide ',
            'orders.*.customer_budget.integer' => 'Le budget global de la commande numéro :position de votre tableau est invalide ',
            'orders.*.intervention_frequency.integer' => 'La fréquence d\'intervention de l\'employé de la commande numéro :position de votre tableau est invalide ',
            'orders.*.intervention_frequency.missing_with' => 'La fréquence d\'intervention de l\'employé n\'est pas prise en compte pour les commande de recrutement ponctuel ',
            'orders.*.cnss.boolean' => 'L\'option CNSS de la commande numéro :position de votre tableau doit etre << true >> OU << false >>',
            'orders.*.cnss.required_if' => 'L\'option CNSS de la commande numéro :position de votre tableau est obligatoire',
            'orders.*.cnss.missing_with' => 'L\'option CNSS n\'est pas prise en compte pour les commande de recrutement ponctuel',
            'orders.*.salary.gt' => 'Le salaire de l\'employé de la commande numéro :position de votre tableau est invalide ',
            'orders.*.service_id.exists' => 'Le service de la commande numéro :position de votre tableau n\'existe pas ',
            'orders.*.service_id.uuid' => 'Le service de la  commande numéro :position de votre tableau n\'est pas un uuid ',
            'orders.*.service_id.distinct' => 'Le service de la commande numéro :position est en doublons ',
        ];
    }
}
