<?php

namespace Core\Modules\Pricing;

use Illuminate\Foundation\Http\FormRequest;

class PricingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->isMethod('POST') ? [
            'designation' => 'required|string',
            "value" => 'required|integer|gt:0',
            'is_rate' => 'required|boolean',
        ] : [
            'designation' => 'sometimes|string',
            "value" => 'sometimes|integer|gt:0',
            'is_rate' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'designation.required' => "La designation du tarif est requise.",
            'value.required' => "La value du tarif est requise.",
            'value.integer' => "La value du tarif doit être un entier.",
            'is_rate.required' => "L'email  du partenaire  est déjà utilisé.",
            'is_rate.boolean' => "Merci de mentionner 0 ou 1 pour renseigner si la valeur est un taux ou non",
           ];
    }
}
