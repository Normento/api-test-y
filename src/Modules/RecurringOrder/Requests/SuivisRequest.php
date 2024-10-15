<?php

namespace Core\Modules\RecurringOrder\Requests;

use App\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SuivisRequest extends FormRequest
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
        $rules =  [
            'resum' => 'required',
            'suivi_date'  => 'required|date|before_or_equal:today',
        ];

        $user = Auth::user();

        // Pour le rôle 'super-admin'
        if ($user->hasRole('super-admin')) {
            $rules['suivi_type'] = [
                'required',
                'string',
                Rule::in(['1', '2']),
            ];

            $rules['employee_id'] = [
                'required_if:suivi_type,2',
                Rule::exists('employees', 'id')->where(function ($query) {
                    $query->where('status', 2);
                }),
            ];

            $rules['user_id'] = [
                'required_if:suivi_type,1',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                        ->where('roles.name', 'customer');
                }),
            ];
        }
        // Pour les rôles 'CO'
        else if ($user->hasRole(['CO'])) {
            $rules['user_id'] = [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                        ->where('roles.name', 'customer');
                }),
            ];
            $rules['employee_id'] = [
                'required_if:suivi_type,1',
                Rule::exists('employees', 'id')->where(function ($query) {
                    $query->where('status', 2);
                }),
            ];

            // Pour les rôles 'responsable-relation-commercial'
        } else if ($user->hasRole(['RRC'])) {
            $rules['user_id'] = [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                        ->where('roles.name', 'customer');
                }),
            ];
        }


        return $rules;
    }


    public function messages()
    {
        return [
            // Messages pour 'resum'
            'resum.required' => 'Le champ "Résumé" est requis.',

            // Messages pour 'suivis_date'
            'suivi_date.required' => 'Le champ "Date de suivi" est requis.',
            'suivi_date.date' => 'Le champ "Date de suivi" doit être une date valide.',
            'suivi_date.before_or_equal' => 'Le champ "Date de suivi" doit être antérieur ou égal à aujourd\'hui.',

            // Messages pour 'suivi_type'
            'suivi_type.required' => 'Le champ "Type de suivi" est requis.',
            'suivi_type.string' => 'Le champ "Type de suivi" doit être une chaîne de caractères.',
            'suivi_type.in' => 'Le champ "Type de suivi" doit être "employee" ou "client".',

            // Messages pour 'employee_id'
            'employee_id.required_if' => 'Le champ "Employé" est requis lorsque le type de suivi est "employee".',
            'employee_id.exists' => 'L\'employé sélectionné est invalide ou n\'a pas le statut actif.',

            // Messages pour 'user_id'
            'user_id.required_if' => 'Le champ "Client" est requis lorsque le type de suivi est "client".',
            'user_id.exists' => 'Le client sélectionné est invalide ou n\'a pas le rôle "customer".',
        ];
    }

}
