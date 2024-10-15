<?php

namespace Core\Modules\RecurringOrder\Requests;

use Core\Modules\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
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
        return [
            'payment_method' => 'required|integer|in:1,2,3,4',
            'phone_number' => 'required_if:payment_method,1,2',
            'type' => 'required:integer,1,2',
            'user_id' =>
                [
                    Rule::requiredIf(function () {
                        return Auth::guest();
                    }),
                    'uuid',
                    Rule::exists(User::class, 'id')
                        ->where(function ($query) {
                            return $query->whereNull('deleted_at');
                        })
                ]
            ,
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'La méthode de paiement est obligatoires',
            'payment_method.integer' => 'La méthode de paiement est invalide',
            'payment_method.in' => 'La méthode de paiement est invalide',
            'phone_number.required_if' => 'Un numéro de téléphone est  obligatoires si la payment_method  est 1 ou 2 ',
        ];
    }
}
