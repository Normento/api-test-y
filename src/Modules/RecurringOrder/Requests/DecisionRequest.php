<?php

namespace Core\Modules\RecurringOrder\Requests;

use Core\Modules\RecurringOrder\Models\Proposition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecisionRequest extends FormRequest
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
            'propositions' => 'required|array',
            'propositions.*.id' => [
                'required',
                "uuid",
                'distinct:strict',
                Rule::exists(Proposition::class, 'id')
                    ->where(function ($query) {
                        return $query->whereNull('deleted_at');
                    }),
            ],
            'propositions.*.action' => 'integer|in:-1,1,2',
            'propositions.*.rejection_reason' => 'required_if:propositions.*.action,-1',

            'propositions.*.interview_location' => 'required_if:propositions.*.action,2',

            'propositions.*.interview_asked_at' => 'required_if:propositions.*.action,2',
        ];
    }

    public function messages(): array
    {
        return [
            'propositions.*.employee_id.distinct' => 'Duplications des employés,veuillez revoir.',
            'propositions.*.employee_id.exists' => 'Employée introuvable.',
            'propositions.*.salary.min' => "Le salaire de l'employé ne doit pas être négatif",
        ];
    }
}
