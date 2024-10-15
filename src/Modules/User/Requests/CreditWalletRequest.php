<?php

namespace Core\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreditWalletRequest extends FormRequest
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
            'amount' => 'required|integer|gt:0',
            'phone_number' => 'required',
            'payment_method' => 'required|integer|in:1,2,4', // 1 => "MTN Mobile Money", 2 => "MOOV Mobile Money   4=> CARTE BANCAIRE"
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Le montant est obligatoire pour faire un dépot',
            'phone_number.required' => 'Le numéro de téléphone est obligatoire',
            'payment_method.required' => 'Le moyen de recharge est obligatoire',
            'amount.integer' => 'Le montant doit être un entier',
            'amount.gt' => 'Le montant du dépôt doit être au supérieur à 0',
        ];
    }
}
