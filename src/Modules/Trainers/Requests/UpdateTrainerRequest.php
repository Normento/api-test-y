<?php


namespace Core\Modules\Trainers\Requests;

use Core\Modules\RecurringService\Models\RecurringService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class UpdateTrainerRequest extends FormRequest
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


    public function rules(): array
    {

        return [
            'full_name' => 'sometimes|string',
            'hourly_rate' => 'sometimes|integer',
            'photo' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            'id_card' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            'phone_number' => ['sometimes',
                Rule::unique('trainers', 'phone_number')->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages()
    {
        return [
            'phone_number.unique' => "Le numéro de téléphone du formateur  est déjà utilisé.",
            'id_card.image' => "La carte d'identité du formateur  est invalide.",
            'photo.image' => "La photo du formateur  est invalide.",
            'hourly_rate.integer' => "Le taux horaire du formateur  est invalide.",
      ];
    }
}
