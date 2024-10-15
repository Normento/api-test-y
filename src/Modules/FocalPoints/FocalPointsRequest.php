<?php


namespace Core\Modules\FocalPoints;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class FocalPointsRequest extends FormRequest
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


    public function rules(): array
    {

        return $this->isMethod('POST') ? [
            'name' => 'required|string',
            "city" => ['required', 'string',
            ],
            'amount' => 'required|integer|gt:0',
        ] : [
            'name' => 'sometimes|string',
            "city" => ['sometimes', 'string',
            ],
            'amount' => 'sometimes|integer|gt:0',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => "Le nom du point focal  est requis.",
            'city.required' => "La ville  du point focal  est requis.",
            'amount.required' => "Le montant du  point focal est requis.",
            'amount.integer' => "Le montant du  point focal  est invalide.",
            'amount.gt' => "Le montant du  point focal  est invalide.",
        ];
    }
}
