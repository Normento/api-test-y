<?php

namespace Core\Modules\RecurringOrder\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class FilterSuiviRequest extends FormRequest
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
    return [
        'type' => 'required|in:1,2',
        'start_date' => 'nullable|date|before_or_equal:today|required_with:end_date',
        'end_date' => 'nullable|date|after:start_date|required_with:start_date',
        'user_id' => [
            Rule::exists('users', 'id')->where(function ($query) {
                $query->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('roles.name', 'customer');
            }),
            'required_if:type,1',
        ],
        'employee_id' => 'nullable|exists:App\Models\Employee,id',
        'package_id' => 'nullable|exists:App\Models\Package,id|required_without:employee_id',
    ];
}


    public function messages()
    {
        return [
            'start_date.before_or_equal'  => 'La date de début ne peut être dans le future',
            'end_date.after'  => 'La date de fin doit être après la date de début',
            'type.required' => 'Le type de filtre de suivi est obligatoire',
            'user_id.required_if' => 'L\'id de l\'utilisateur est oblgatoire quand le type de filtre est suivi client',
            'employee_id.required_if' => 'L\'id de l\'employé est oblgatoire quand le type de filtre est suivi  employé',
            'user_id.exists' => 'L\'id de l\'utilisateur est incorrect',
            'employee_id.exists' => 'L\'id de l\'employé est incorrect',
        ];
    }
}
