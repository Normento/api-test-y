<?php

namespace Core\Modules\RecurringOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSuiviRequest extends FormRequest
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
            'resum' => 'required',
            'suivis_date'  => 'required|date|before_or_equal:today',
        ];
    }

    public function messages()
    {
        return [
            'resum.required' => 'Le résumé du suivi est oblgatoire',
            'suivis_date.required' => 'La date du suivi est obligatoire',
            'suivis_date.date' => 'La date du suivi  doit être une date ',
            'suivis_date.before_or_equal' => 'La date du suivi ne doit pas  être une date dans le futur '
        ];
    }
}
