<?php


namespace Core\Modules\Trainers\Requests;

use Core\Modules\RecurringService\Models\RecurringService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class UpdateTrainerServiceRequest extends FormRequest
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
            "skill" => 'sometimes|string',
            'years_of_experience' => 'sometimes|string',
        ];
    }

    public function messages()
    {
        return [

        ];
    }
}
