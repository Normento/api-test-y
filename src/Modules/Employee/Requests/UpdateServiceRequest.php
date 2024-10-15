<?php

namespace Core\Modules\Employee\Requests;

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
            "about" => 'sometimes|string',
            'years_of_experience' => 'sometimes|string',
            'salary_expectation' => 'sometimes|integer'
        ];
    }

    public function messages(): array
    {
        return [

            'about.required' => "Le résumé de la compétence de l'employé sur le service est requis.",
            'about.string' => "Le résumé de la compétence de l'employé sur le  service doit être une chaîne de caractères.",
            'years_of_experience.required' => "L'année d'expérience de l'employé sur le service est requise.",
            'years_of_experience.string' => "L'année d'expérience de l'employé sur le  service doit être une chaîne de caractères.",

            'salary_expectation.required' => "La prétention salariale sur le service est requise.",
            'salary_expectation.integer' => "La prétention salariale est invalide.",
        ];
    }
}
