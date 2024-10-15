<?php

namespace Core\Modules\Notification\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PushNotificationRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'title' => 'required|string',
            'content' => 'required|string',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048'
        ];
    }
    public function messages(): array
    {
        return [
            'title.required' => "Le titre est obligatoire",
            'content.required' => "Le contenu est obligatoire",
            'image.image' => "L'image est invalide",
            'image.mimes' => "L'image est invalide",
            'image.max' => "L'image trop lourde",
        ];
    }
}
