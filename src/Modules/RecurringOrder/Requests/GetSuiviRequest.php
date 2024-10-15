<?php

namespace Core\Modules\RecurringOrder\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GetSuiviRequest extends FormRequest
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
    $user = Auth::user();
    $rules = [];

    // Pour le rôle 'superAdmin'
    if ($user->hasRole('super-admin')) {
        $rules['type'] = ['nullable', 'string', Rule::in(['1', '2'])];

        $rules['user_id'] = [
            'required_if:suivi_type,1',
            Rule::exists('users', 'id')
        ];

        $rules['employee_id'] = [
            'required_if:suivi_type,2',
            'exists:employees,id',
        ];
    }

    // Pour les rôles 'assistantRH' et 'adminRH'
    if ($user->hasAnyRole(['AA'])) {
        $rules['employee_id'] = [
            'required',
            'exists:employees,id',
        ];
    }

    // Pour les rôles 'responsable Relation Client' et 'charge D'operation'
    if ($user->hasAnyRole(['RRC', 'CO'])) {
        $rules['user_id'] = [
            'required',
            Rule::exists('users', 'id')->where(function ($query) {
                $query->whereHas('role', function ($query) {
                    $query->where('name', 'customer');
                });
            }),
        ];
    }

    return $rules;
}

public function messages()
    {
        return [
            // Messages pour 'suivi_type'
            'suivi_type.required' => 'Le champ "Type de suivi" est requis.',
            'suivi_type.string' => 'Le champ "Type de suivi" doit être une chaîne de caractères.',
            'suivi_type.in' => 'Le champ "Type de suivi" doit être "employee" ou "client".',

            // Messages pour 'user_id'
            'user_id.required_if' => 'Le champ "Utilisateur" est requis lorsque le type de suivi est "client".',
            'user_id.exists' => 'L\'utilisateur sélectionné est invalide ou n\'est pas un client.',

            // Messages pour 'employee_id'
            'employee_id.required_if' => 'Le champ "Employé" est requis lorsque le type de suivi est "employee".',
            'employee_id.required' => 'Le champ "Employé" est requis.',
            'employee_id.exists' => 'L\'employé sélectionné est invalide.',
        ];
    }

}
