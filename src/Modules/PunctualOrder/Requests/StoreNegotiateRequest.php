<?php

namespace Core\Modules\PunctualOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNegotiateRequest extends FormRequest
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
            'negotiation' => 'required|string',
            'status' => 'required|integer|in:1',
        ];
    }

    public function messages()
    {
        return [
            'status.required' => 'Le status doit être renseigné',
            'status.integer' => 'Le status n\'est pas valid',
            'status.in' => 'Le status doit être égal à 1',
            'negotiation.required' => 'Le champs de négociation ne peut être vide',
        ];
    }
}
