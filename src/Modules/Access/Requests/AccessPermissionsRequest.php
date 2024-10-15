<?php

namespace Core\Modules\Access\Requests;

use Core\Modules\Access\Models\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccessPermissionsRequest extends FormRequest
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
            'permissions' => ['required', 'array'],
            'permissions.*' => [
                'string',
                'distinct:strict',
                Rule::exists(Permission::class, 'name')

            ],
        ];
    }

    public function messages(): array
    {
        return [
            'permissions.required' => 'Les  permissions du rôle sont obligatoire',
            'permissions.*.exists' => 'La permission numéro :position de votre tableau n\'existe pas ',
            'permissions.*.distinct' => 'La permission numéro :position est en doublons ',
        ];
    }
}
