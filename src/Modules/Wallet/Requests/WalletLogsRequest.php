<?php

namespace Core\Modules\Wallet\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WalletLogsRequest extends FormRequest
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
        if ($this->isMethod("post")) {
            return [
                "operation_type" => "sometimes|string|nullable|in:withdraw,deposit",
                'start_date'  => 'sometimes|nullable|date|before_or_equal:today|required_with:end_date|nullable',
                'end_date'  => 'sometimes|nullable|date|after:start_date|required_with:start_date|nullable',
            ];
        }
        return [];
    }
}
