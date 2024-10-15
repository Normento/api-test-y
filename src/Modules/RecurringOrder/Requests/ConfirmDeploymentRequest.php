<?php

namespace Core\Modules\RecurringOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmDeploymentRequest extends FormRequest
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
            'date' => 'required|date|before_or_equal:today',
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'La date est obligatoire',
            'date.date' => 'Le format de la date est invalide',
            'date.before_or_equal' => 'La date doit être aujourd\'hui ou dans le passé',
        ];
    }
}
