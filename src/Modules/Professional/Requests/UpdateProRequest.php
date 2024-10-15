<?php

namespace Core\Modules\Professional\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'profile_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',

            'full_name' => 'sometimes|nullable|string',
            'enterprise_name' => 'sometimes|nullable|string',
            'address' => 'sometimes|nullable|string',
            'phone_number' => ["sometimes", "nullable", Rule::unique('professionals')->ignore($this->pro->id)->whereNull('deleted_at')],
            'email' => ["nullable", "sometimes", "email", Rule::unique('professionals')->ignore($this->pro->id)->whereNull('deleted_at'), 'email:rfc,dns'],
            'status' => "sometimes|integer"
        ];
    }

    public function messages()
    {
        return [
            'email.email' => 'Format de mail incorrect',
            'phone_number.unique' => 'Le numéro de téléphone est déja utilisé par un autre professionel',
            'email.unique' => 'Ce mail est déja utilisé par un autre professionel'
        ];
    }
}
