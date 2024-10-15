<?php

namespace Core\Modules\PunctualService\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
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
    public function rules()
    {
        return [
            'name' => ['sometimes', 'string', Rule::unique('punctual_services')->ignore($this->service->id)->withoutTrashed('deleted_at')],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'fixed_price' => "sometimes|boolean",
            'is_highlighted' => "sometimes|boolean",
            'is_archived' => "sometimes|boolean"
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Le nom du service est obligatoire',
            'name.unique' => 'Ce service existe déja',
            'fixed_price.boolean' =>'Veuillez choisir entre true ou false',
            'is_archived.boolean' =>'Veuillez choisir entre true ou false',
            'image.image' => "Veuillez choisir une image pour l'image du service",
            'image.mimes' => 'Seuls les formats JPEG,PNG,JPG,GIF sont acceptés',
        ];
    }
}
