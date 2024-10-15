<?php

namespace Core\Modules\PunctualOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeNoteRequest extends FormRequest
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
            'payments' => 'required|array',
            'payments.*.payment_id' => 'required|uuid|exists:payments,id',
            'payments.*.note' => 'required|numeric|min:1|max:5',
            'payments.*.comment' => 'required|string',
        ];
    }

    /**
     * Custom messages for validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'payments.required' => 'Vous devez fournir au moins un paiement.',
            'payments.array' => 'Le format des paiements doit être un tableau.',
            'payments.*.payment_id.required' => 'L\'identifiant du paiement est obligatoire.',
            'payments.*.payment_id.uuid' => 'L\'identifiant du paiement doit être un UUID valide.',
            'payments.*.payment_id.exists' => 'Le paiement sélectionné n\'existe pas.',
            'payments.*.note.required' => 'La note est obligatoire.',
            'payments.*.note.numeric' => 'La note doit être un nombre.',
            'payments.*.note.min' => 'La note doit être au minimum de 1.',
            'payments.*.note.max' => 'La note doit être au maximum de 5.',
            'payments.*.comment.required' => 'Le commentaire est obligatoire.',
            'payments.*.comment.string' => 'Le commentaire doit être une chaîne de caractères.',
        ];
    }
}
