<?php

namespace Core\Modules\PunctualService\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
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
            'name' =>  ['required', 'string', Rule::unique('punctual_services')->whereNull('deleted_at')],
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'fixed_price' => 'required|boolean',
        ];
    }


    public function messages()
    {
        return [
            'name.required' => 'Le nom du service est obligatoire',
            'name.unique' => 'Ce service déja',
            'image.required' => 'L\'image du service est obligatoire',
            'fixed_price.required' =>'Veuillez spécifier le type de service, s\'il s\'agit d\'un prix fixe ou variable.',
            'fixed_price.boolean' =>'Veuillez choisir entre true ou false',
            'image.image' => "Veuillez choisir une image pour l'image du service",

        ];
    }
}
