<?php

namespace Core\Modules\RecurringOrder\Requests;

use Core\Modules\Employee\Models\Employee;
use Core\Modules\RecurringOrder\Models\RecurringOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePropositionRequest extends FormRequest
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
         'employee_id' => [
                'sometimes',
                "uuid",
                'distinct:strict',
                Rule::exists(Employee::class, 'id')
                    ->where(function ($query) {
                        return $query->whereNull('deleted_at');
                    }),
            ],
            'salary' => 'integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.distinct' => 'Duplications des employés,veuillez revoir.',
            'employee_id.exists' => 'Employée introuvable.',
            'salary.min' => "Le salaire de l'employé ne doit pas être négatif",
        ];
    }
}
