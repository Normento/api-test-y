<?php

namespace Core\Modules\RecurringOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TerminateEmployeeContractRequest extends FormRequest
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
            'proposition_id' => 'required|exists:propositions,id',
            'date'  => 'required|date',
            'reason' => 'required_if:is_professional_break,0',
            'type' => "required_if:is_professional_break,0|in:0,1",  //0 incompétence 1 Autre
            "is_professional_break" => "required|in:0,1"
        ];
    }

    public function messages()
    {
        return [
            'date.required' => "Veuillez indiquer la date à laquelle l'employé a cessé de travailler.",
            'reason.required_if' => "La raison de la résiliation est obligatoire si ce n'est pas une pause professionnelle.",
            'type.required_if' => "Le type de résiliation est obligatoire si ce n'est pas une pause professionnelle.",
            'type.in' => "Le type de résiliation doit être soit 'incompétence' (0), soit 'autre' (1).",
            'is_professional_break.required' => "Veuillez indiquer si la résiliation est due à une pause professionnelle.",
            'is_professional_break.in' => "la pause professionnelle doit etre O ou 1"
        ];
    }
}
