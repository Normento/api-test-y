<?php

namespace Core\Modules\Professional\Requests;

use Core\Modules\PunctualService\Models\PunctualService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;


class RegisterProRequest extends FormRequest
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
            'profile_image' => 'required|image',
            "enterprise_name" => "sometimes|string",
            'email' => [
                'sometimes',
                'email:rfc,dns',
                Rule::unique('professionals', 'email')->whereNull('deleted_at'),
            ],
            'full_name' => 'required|string',
            'address' => 'required|string',
            'phone_number' => ['required', Rule::unique('professionals', 'phone_number')->whereNull('deleted_at')],
            'services' => 'required|array',
            "services.*.id" => [Rule::exists(PunctualService::class, 'id')->where(function ($query) {
                return $query->where('deleted_at', null);
            }), 'required', 'distinct:strict', "uuid"],
            "services.*.description" => 'required|string',
            "services.*.price" => [Rule::requiredIf(function () {
                // Accessing the array index
               // $index = explode('.', $attribute)[1];
                // Use the array index to exclude the current record from validation
               // return DB::table('punctual_services')
                //    ->where('id', '=', $validator->getData()['services'][$index]['id'])
                //    ->first()->fixed_price;
            })],
            'services.*.works_picture' => 'required|array',
            'services.*.works_picture.*' => 'image|mimes:jpeg,png,jpg,svg|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'profile_image.required' => "L'image de profil est requise.",
            'profile_image.image' => "L'image de profil doit être au format image.",
            'full_name.required' => "Le nom complet est requis.",
            'full_name.string' => "Le nom complet doit être une chaîne de caractères.",
            'address.required' => "L'adresse est requise.",
            'email.email' => "L'email est invalide.",
            'email.rfc' => "L'email est invalide.",
            'address.string' => "L'adresse doit être une chaîne de caractères.",
            'phone_number.required' => "Le numéro de téléphone est requis.",
            'phone_number.unique' => "Ce numéro de téléphone est déjà utilisé par un professionnel.",
            'services.required' => "Au moins un service est requis.",
            'services.array' => "Les services doivent être au format tableau.",
            'services.*.id.exists' => "L'ID du service n'existe pas ou a été supprimé.",
            'services.*.id.required' => "L'ID du service est requis.",
            'services.*.id.distinct' => "Les ID des services ne doivent pas être en double.",
            'services.*.id.uuid' => "L'ID du service doit être un UUID valide.",
            'services.*.description.required' => "La description du service est requise.",
            'services.*.description.string' => "La description du service doit être une chaîne de caractères.",
            'services.*.works_picture.array' => "Les images du travail doivent être au format tableau.",
            'services.*.works_picture.*.image' => "Les images du travail doivent être au format image.",
            'services.*.works_picture.*.mimes' => "Les images du travail doivent être au format jpeg, png, jpg, svg.",
            'services.*.works_picture.*.max' => "Les images du travail ne doivent pas dépasser 2048 Ko."
        ];
    }
}
