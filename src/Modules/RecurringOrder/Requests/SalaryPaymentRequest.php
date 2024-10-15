<?php

namespace Core\Modules\RecurringOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SalaryPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Autoriser uniquement si l'utilisateur a les permissions adéquates
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [];

        if ($this->isMethod('post')) {
            $rules["user_id"] = ["present", "nullable", "exists:users,id", "integer", "sometimes"];
            $rules["employee_id"] = ["present", "nullable", "exists:employees,id", "integer", "sometimes"];
            $rules["month_salary"] = ["present", "nullable", "string", "sometimes"];
            $rules["employee_received_salary_advance"] = ["present", "nullable", "boolean", "sometimes"];
            $rules["auto_send"] = ["present", "nullable", "boolean", "sometimes"];
            $rules["year"] = ["present", "nullable", "integer", "sometimes"];
            $rules["status"] = ["present", "nullable", "boolean", "sometimes"];
            $rules['cnss'] = ["required", "boolean", "exclude"];

            if (Auth::user()->hasAnyRole(['super-admin', 'RRC'])) {
                $rules['co_id'] = [
                    "present", "integer", "nullable", "sometimes",
                    Rule::exists('users', 'id')->where(function ($query) {
                        $query->whereHas('roles', function ($query) {
                            $query->where('name', 'CO');
                        });
                    })
                ];
            }
        }

        return $rules;
    }

    /**
     * Custom messages for validation.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'user_id.exists' => 'L\'utilisateur sélectionné n\'existe pas.',
            'employee_id.exists' => 'L\'employé sélectionné n\'existe pas.',
            'month_salary.string' => 'Le mois de salaire doit être une chaîne de caractères valide.',
            'employee_received_salary_advance.boolean' => 'Le champ "Avance de salaire reçue" doit être un booléen.',
            'auto_send.boolean' => 'Le champ "Envoi automatique" doit être un booléen.',
            'year.integer' => 'L\'année doit être un entier valide.',
            'status.boolean' => 'Le statut doit être un booléen.',
            'cnss.required' => 'Le champ CNSS est obligatoire.',
            'co_id.exists' => 'Le chargé d\'operation sélectionné n\'existe pas ou ne correspond pas aux critères requis.'
        ];
    }
}
