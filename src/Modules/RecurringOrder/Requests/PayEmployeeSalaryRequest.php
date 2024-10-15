<?php

namespace Core\Modules\RecurringOrder\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Core\Modules\RecurringOrder\Models\Payment;

class PayEmployeeSalaryRequest extends FormRequest
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
            'phoneNumber' => 'required_if:payment_method,1,2',
            'payment_method' => 'required|integer|in:1,2,3,4',
            'payment' => ['required', 'array', Rule::exists(Payment::class, 'id')
            ->where(function ($query) {
                return $query->whereNull('deleted_at');
            })],

            'date' => [
                'nullable',
                'date',
                'after:today',
            ],
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'phoneNumber.required_if' => 'Le numéro de téléphone est requis pour les méthodes de paiement Mobile Money (MTN ou MOOV).',
            'payment_method.required' => 'La méthode de paiement est obligatoire.',
            'payment_method.integer' => 'La méthode de paiement doit être un nombre entier.',
            'payment_method.in' => 'La méthode de paiement doit être l’un des suivants : 1 (MTN Mobile Money), 2 (MOOV Mobile Money), 3 (Portefeuille YLOMI), 4 (CARTE BANCAIRE).',
            'payment.required' => 'Le paiement est requis.',
            'payment.*.exists' => 'Le paiement sélectionné n\'existe pas ou a été supprimé.',
            'date.date' => 'La date doit être une date valide.',
            'date.after' => 'La date de paiement doit être dans le futur, elle ne peut pas être aujourd\'hui ni dans le passé.',
        ];
    }
}
