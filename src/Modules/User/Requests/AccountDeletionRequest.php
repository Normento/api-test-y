<?php

namespace Core\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AccountDeletionRequest extends FormRequest
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
            'reason' => [
                Rule::requiredIf(function () {
                    return Auth::id() == $this->user->id;
                })
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => "Le motif de la suppression de compte  est obligatoire",
        ];
    }

}
