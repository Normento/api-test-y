<?php

namespace Core\Modules\Access\Requests;

use Core\Modules\Access\Models\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccessRequest extends FormRequest
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
        $rules = [
            'name' => [
                'string',
            ],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => [
                'string',
                'distinct:strict',
                Rule::exists(Permission::class, 'name')
            ],
        ];
        if ($this->isMethod('PATCH')) {
            $rules['name'][] = 'sometimes';
            $rules['name'][] = Rule::unique('roles', 'name')
                ->ignore($this->role->id);
        } else {
            $rules['name'][] = 'required';
            $rules['name'][] = Rule::unique('roles', 'name');
        }
        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du rôle est obligatoire',
            'permissions.*.string' => 'Le tableau des permissions doit contenir uniquement des chaines de caractère',
            'name.unique' => 'Ce role existe déja',
            'name.string' => 'Le nom du rôle doit être une chaine de caractere',
            'permissions.*.exists' => 'La permission numéro :position de votre tableau n\'existe pas ',
            'permissions.*.distinct' => 'La permission numéro :position est en doublons ',
        ];
    }

}
