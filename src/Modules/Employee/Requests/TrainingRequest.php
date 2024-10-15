<?php

namespace Core\Modules\Employee\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrainingRequest extends FormRequest
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

    public function rules(): array
    {
        return [
            'services' => 'required|array',
            'start_date' => [
                Rule::requiredIf(function () {
                    return str_ends_with($this->url(), '/training');
                }),
                'date', 'before_or_equal:today'
            ],
            "services.*" => [
                'required',
                "uuid",
                'distinct',
                Rule::exists('employee_recurring_service', 'recurring_service_id')
                    ->where(function ($query) {
                        return $query->where('employee_id', $this->employee->id)->where('deleted_at', null);
                    })
            ]

        ];
    }

    public function messages(): array
    {
        return [
            "services.required" => "Le champ des services est requis.",
            "start_date.required" => "Le date de début de la formation est requis.",
            "start_date.date" => "Le date de début de la formation est invalide.",
            "start_date.before_or_equal" => "Le date de début de la formation est invalide.",
            "services.array" => "Le champ des services doit être un tableau.",
            "services.*.required" => "Chaque élément du tableau des services est requis.",
            "services.*.uuid" => "L'id de service invalid",
            "services.*.exists" => "L'employé ne fournit pas ce service."
        ];
    }


}
