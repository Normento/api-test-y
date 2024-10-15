<?php

namespace Core\Modules\Employee\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinishTrainingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'end_date' => ['required', 'date', "after:{$this->training->start_date}"],
            'observation' => 'required|string',
            'is_recycling' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            "end_date.required" => "La date de fin de la formation est obligatoir.",
            "end_date.date" => "La date de fin de la formation est invalide .",
            "end_date.after" => "La date de fin de la formation est invalide .",
            "observation.required" => "Une observation sur la formation est obligation",
            "is_recycling.required" => "L'option recyclage  est requise.",
            "is_recycling.boolean" => "L'option recyclage doit être 0 ou 1",
        ];
    }


}
