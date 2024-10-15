<?php

namespace Core\Modules\Professional\Requests;

use Illuminate\Foundation\Http\FormRequest;


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


    public function rules(): array
    {
        return [
            "description" => 'sometimes|string',
            'works_picture' => 'sometimes|array|min:1',
            'works_picture.*' => 'image|mimes:jpeg,png,jpg,svg|max:2048'
        ];
    }

    public function messages(): array
    {
        return [
            'description.string' => "La description du service doit être une chaîne de caractères.",
            'works_picture.array' => "Les images du travail doivent être au format tableau.",
            'works_picture.*.image' => "Les images du travail doivent être au format image.",
            'works_picture.*.mimes' => "Les images du travail doivent être au format jpeg, png, jpg, svg.",
            'works_picture.*.max' => "Les images du travail ne doivent pas dépasser 2048 Ko."
        ];
    }
}
