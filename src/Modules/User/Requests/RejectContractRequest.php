<?php

namespace Core\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectContractRequest extends FormRequest
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
            'contract_rejection_reason' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'contract_rejection_reason.required' => 'La raison de votre déapprobation est obligatoire',
        ];
    }
}
