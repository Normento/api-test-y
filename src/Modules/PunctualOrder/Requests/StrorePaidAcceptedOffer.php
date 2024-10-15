<?php

namespace Core\Modules\PunctualOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StrorePaidAcceptedOffer extends FormRequest
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
            "accept_button_has_been_clicked" => 'required|accepted',
            "payment_button_has_been_clicked" => 'required|accepted',
            "payment_method" => 'required|integer|gt:0',
            "phoneNumber" =>'required',
            "amount" => [
                'sometimes',
                'required_if:payment_type,1',
                'integer',
                'gt:0',
            ],
            'payment_type' => 'required|integer|between:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'accept_button_has_been_clicked.accepted' =>'Veuillez accepter l\'offre.',
            'payment_button_has_been_clicked.accepted' =>'Veuillez prendre par le bouton de paiement.',
            'payment_method.required' => 'Vous devez choisir un moyen de paiement.',
            'payment_method.gt' => 'Ce moyen de paiement n\'est pas encore pris en compte.',
            'payment_method.integer' => 'Moyen de paiement invalide.',
            'phoneNumber.required' => 'Vous devez renseigner votre numéro.',
            'amount.required_if' => 'Le montant est requis pour un paiement échelonné.',
            'amount.integer' => 'Le prix n\'est pas valide.',
            'amount.gt' => 'Le montant ne peut pas être 0.',

            'payment_type.required' => 'Vous devez choisir un type de paiement.',
            'payment_type.between' => 'Ce type de paiement n\'est pas encore pris en compte.',
            'payment_type.integer' => 'Type de paiement invalide.',
        ];
    }
}
