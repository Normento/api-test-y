<?php

namespace Core\Modules\Professional\Requests;

use Core\Modules\PunctualService\Models\PunctualService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;


class AddServicesRequest extends FormRequest
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
            'services' => 'required|array',
            "services.*.description" => 'required|string',
            "services.*.price" => [
              /*  Rule::requiredIf(function ($value, $attribute, $parameters, $validator) {
                    // Accessing the array index
                    $index = explode('.', $attribute)[1];
                    // Use the array index to exclude the current record from validation
                    return DB::table('punctual_services')
                        ->where('id', '=', $validator->getData()['services'][$index]['id'])
                        ->first()->fixed_price;
                })*/
            ],
            'services.*.works_picture' => 'required|array',
            'services.*.works_picture.*' => 'image|mimes:jpeg,png,jpg,svg|max:2048',
            "services.*.id" => [
                'required', 'distinct:strict', 'uuid',
                Rule::exists(PunctualService::class, 'id')
                    ->where(function ($query) {
                        return $query->whereNull('deleted_at')
                            ->where('is_archived', false);
                    }), Rule::unique('professional_punctual_service', 'punctual_service_id')
                    ->where(function ($query) {
                        return $query->where('professional_id', $this->pro->id)
                            ->whereNull('deleted_at');
                    })
            ],

        ];
    }


    public function messages(): array
    {
        return [
            'services.required' => "Au moins un service est requis.",
            'services.array' => "Les services doivent être au format tableau.",
            'services.*.id.exists' => "L'ID du service n'existe pas ou a été supprimé.",
            'services.*.id.required' => "L'ID du service est requis.",
            'services.*.id.distinct' => "Les ID des services ne doivent pas être en double.",
            'services.*.id.unique' => "Veuillez sélectionner les services que ne fournit pas encore ce pro",
            'services.*.id.uuid' => "L'ID du service doit être un UUID valide.",
            'services.*.description.required' => "La description du service est requise.",
            'services.*.price.integer' => "La prix n'est pas valide.",
            'services.*.price.gt' => 'Le prix doit être supérieur à zéro',
            'services.*.description.string' => "La description du service doit être une chaîne de caractères.",
            'services.*.works_picture.array' => "Les images du travail doivent être au format tableau.",
            'services.*.works_picture.*.image' => "Les images du travail doivent être au format image.",
            'services.*.works_picture.*.mimes' => "Les images du travail doivent être au format jpeg, png, jpg, svg.",
            'services.*.works_picture.*.max' => "Les images du travail ne doivent pas dépasser 2048 Ko."
        ];
    }
}
