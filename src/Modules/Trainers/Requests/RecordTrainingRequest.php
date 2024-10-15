<?php

namespace Core\Modules\Trainers\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordTrainingRequest extends FormRequest
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
            'training_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'activity' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'training_date.required' => 'La date de formation est requise.',
            'training_date.date' => 'Format de date invalide pour la date de formation.',
            'start_time.required' => 'L\'heure de début est requise.',
            'start_time.date_format' => 'Format d\'heure invalide pour l\'heure de début.',
            'end_time.required' => 'L\'heure de fin est requise.',
            'end_time.date_format' => 'Format d\'heure invalide pour l\'heure de fin.',
            'end_time.after' => 'L\'heure de fin doit être après l\'heure de début.',
            'activity.required' => 'Merci de renseigner le contenu de la formation effectué',
        ];
    }
}
