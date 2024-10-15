<?php

namespace Core\Modules\PunctualOrder\Requests;

use Core\Modules\PunctualService\Models\PunctualService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;

class StoreOrderRequest extends FormRequest
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
            "budget" => 'required|integer|gt:0',
            "description" => 'required|string',
            "desired_date" => 'required|date|date_format:Y-m-d H:i|after_or_equal:today',
            "address" => 'required|string',
            "payment_method" => 'required|integer',
            "service_id" => [
                'required', 'uuid',
                Rule::exists(PunctualService::class, 'id')
                    ->where(function ($query) {
                        return $query->whereNull('deleted_at')
                            ->where('is_archived', false);
                    })
            ],
            "phoneNumber" => [new RequiredIf(function () {
                $service = PunctualService::find(request()->input('service_id'));
                return $service && $service->fixed_price === false;
            })],
            'pictures' => 'nullable|array|max:3',
            'pictures.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'budget.required' => 'Le budget est obligatoire',
            'budget.gt' => 'Le budget doit être supérieur à zéro',
            'budget.integer' => 'Le budget est invalid',
            'description.required' => 'La description est obligatoire',
            'desired_date.required' => 'La date est requise',
            'desired_date.date' => 'La date n\'est pas valide',
            'desired_date.date_format' => 'La date doit être au format valide Année-Mois-Jour Heure-Minute',
            'desired_date.after_or_equal' => 'La date doit être égale ou postérieure à aujourd\'hui',
            'address.required' => 'L\'adresse est obligatoire',
            'payment_method.integer' => 'Moyen de payment invalid',
            'service_id.required' => 'Vous devez choisir un service',
            'phoneNumber.required_if' => 'Vous devez renseigner votre numéro.',
            'service_id.uuid' => 'Le format du service séléctionné est invalid',
            'service_id.exists' => 'Le service séléctionné n\'existe pas',
            'pictures.max' => 'Plus de trois images ne peuvent être sélectionnée',
            'pictures.*.image' => 'Le fichier doit être une image',
            'pictures.*.mimes' => 'Seuls les formats JPEG, PNG et PDF sont acceptés',
            'pictures.*.max' => 'Chaque image ne peut pas dépasser 2 Mo',
        ];
    }
}
