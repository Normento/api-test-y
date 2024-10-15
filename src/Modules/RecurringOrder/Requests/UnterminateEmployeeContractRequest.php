<?php

namespace Core\Modules\RecurringOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnterminateEmployeeContractRequest extends FormRequest
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
            "with_defalcation" => "required|boolean",
            'date'  => 'date|required_if:with_defalcation,true',
            'proposition_id' => 'required|exists:propositions,id|uuid'
        ];
    }

    public function messages()
    {
        return [
            'with_defalcation.required' => "Est ce avec une défalcation ?",
            "date.required_if" => "Veuillez préciser la date de reprise de l'employé",
            "proposition_id.required" => "l'id de la proposition est obligatoire",
            "proposition_id.exists" => "la proposition n'existe pas",
        ];
    }
}
