<?php

namespace Core\Modules\PunctualOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePunctualOrder extends FormRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            "budget" => 'sometimes|integer|gt:0',
            "description" => 'sometimes|string',
            "desired_date" => 'sometimes|date|date_format:Y-m-d H:i|after_or_equal:today',
            "address" => 'sometimes|string',
            "status" => 'sometimes|integer|in:3',
        ];
    }

    public function messages(): array
    {
        return [
            'budget.gt' => 'Le budget doit être supérieur à zéro',
            'budget.integer' => 'Le budget est invalid',
            'status.integer' => 'Le status n\'est pas valid',
            'status.in' => 'Le status doit être égal à 3',
            'desired_date.date' => 'La date n\'est pas valide',
            'desired_date.date_format' => 'La date doit être au format valide Année-Mois-Jour Heure-Minute',
            'desired_date.after_or_equal' => 'La date doit être égale ou postérieure à aujourd\'hui',
        ];
    }
}
