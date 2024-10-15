<?php

namespace Core\Modules\RecurringOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StatisticStartDateAndEndDateRequest extends FormRequest
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
      if($this->isMethod("post"))
      {
        return [
            'start_date'  => 'required|date|before_or_equal:today',
            'end_date'  => 'required|date|after:start_date',
        ];
      }
      return [];

    }

    public function messages()
    {
        return [
            'start_date.before_or_equal'  => 'La date de début ne peut être dans le future',
            'end_date.after'  => 'La date de fin doit être après la date de début',
        ];
    }
}
