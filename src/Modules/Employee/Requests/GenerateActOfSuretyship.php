<?php

namespace Core\Modules\Employee\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateActOfSuretyship extends FormRequest
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
            "full_name" => "required|string",
            "phone_number" => "required|integer",
            "family_link" => "required|string",
            "piece_expire_at" => "required|date|after_or_equal:today",
            "piece_number" => "required|string",
            "address" => "required|string",
            "piece_delivered_by" => "required|string",
        ];
    }

    public function messages()
    {
        return [
            "full_name.required" => "Le champ nom complet est obligatoire.",
            "full_name.string" => "Le champ nom complet doit être une chaîne de caractères.",
            "phone_number.required" => "Le champ numéro de téléphone est obligatoire.",
            "phone_number.integer" => "Le champ numéro de téléphone doit être un nombre entier.",
            "family_link.required" => "Le champ lien familial est obligatoire.",
            "family_link.string" => "Le champ lien familial doit être une chaîne de caractères.",
            "piece_expired_at.required" => "Le champ date d'expiration du document est obligatoire.",
            "piece_expired_at.date" => "Le champ date d'expiration du document doit être une date valide.",
            "piece_expired_at.after_or_equal" => "Le champ date d'expiration du document doit être aujourd'hui ou une date ultérieure.",
            "piece_number.required" => "Le champ numéro du document est obligatoire.",
            "piece_number.string" => "Le champ numéro du document doit être une chaîne de caractères.",
            "address.required" => "Le champ adresse est obligatoire.",
            "address.string" => "Le champ adresse doit être une chaîne de caractères.",
            "piece_delivered_by.required" => "Le champ délivré par est obligatoire.",
            "piece_delivered_by.string" => "Le champ délivré par doit être une chaîne de caractères.",
        ];
    }
}
