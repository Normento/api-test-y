<?php

namespace Core\Modules\RecurringOrder\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;


class PublishedSuiviRequest extends FormRequest
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
            'suivi_type' => [
                Rule::requiredIf(Auth::user()->hasRole('super-admin')),
                'string',
                Rule::in(['employee', 'client']),
            ],
        ];
    }

    public function messages()
    {
        return [
            'suivi_type.required' => 'Le champ "Type de suivi" est requis pour les super-administrateurs.',
            'suivi_type.string' => 'Le champ "Type de suivi" doit être une chaîne de caractères.',
            'suivi_type.in' => 'Le champ "Type de suivi" doit être soit "employee" soit "client".',
        ];
    }
}
