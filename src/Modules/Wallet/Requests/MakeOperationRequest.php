<?php

namespace Core\Modules\Wallet\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MakeOperationRequest extends FormRequest
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
            'operation_type' => 'required|in:0,1', //0 pour retrait et 1 pour dépot
            "amount" => "required|integer|gt:0",
            "trace" => "required|string"
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => "Veuillez préciser le montant à défalquer",
            'amount.gt' => "Le montant à défalquer doit être supérieur à 0",
            "trace.required" => "Veuillez ajouter la trace de l'opération"
        ];
    }
}
