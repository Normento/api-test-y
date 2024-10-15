<?php

namespace Core\Modules\PunctualOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StroreRejectedOfferRequest extends FormRequest
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
            "rejected_reason" => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'rejected_reason.required' =>'Veuillez preciser la raison du rejet.',

        ];
    }
}
