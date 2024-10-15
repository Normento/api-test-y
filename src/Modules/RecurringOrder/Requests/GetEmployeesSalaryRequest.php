<?php

namespace Core\Modules\RecurringOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetEmployeesSalaryRequest extends FormRequest
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
        if ($this->isMethod("post")) {
            return [
                "employee_id" => [
                    "nullable",
                    "present",
                    Rule::exists('employees', 'id')->where(function ($query) {
                        $query->where('status', 2);
                    }),
                ],
                "month_salary" => [
                    "nullable",
                    "present",
                    "string",
                    Rule::in(['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre']),
                ],
                "year" => "nullable|present|integer",
                "status" => "nullable|present|boolean",
                "client_payed" => "nullable|present|boolean",
            ];
        }

        return [];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'employee_id.exists' => "L'employé sélectionné n'est pas valide ou n'est pas actif.",
            'month_salary.in' => "Le mois du salaire doit être l'un des suivants : janvier, février, mars, avril, mai, juin, juillet, août, septembre, octobre, novembre, décembre.",
            'year.integer' => "L'année doit être un nombre entier valide.",
            'status.boolean' => "Le statut doit être un booléen valide (true ou false).",
            'client_payed.boolean' => "Le champ client payé doit être un booléen valide (true ou false).",
        ];
    }
}
