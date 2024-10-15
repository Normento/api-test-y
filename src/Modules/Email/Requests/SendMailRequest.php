<?php

namespace Core\Modules\Email\Requests;

use Core\Modules\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class SendMailRequest extends FormRequest
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
        $isTagged = $this->input('is_tagged');

        $rules = [
            'recipients' => 'required|array',
            'recipients.*' => 'required|email|distinct:strict',
            'cc' => 'array|nullable',
            'cc.*' => 'distinct:strict',
            'attachments' => 'array',
            'subject' => "required|string",
            'body' => "required|string",
            'attachments.*' => [
                File::types(['pdf', 'jpeg', 'png', 'doc', 'sql'])
                    ->max(12 * 1024),
            ],
            "is_tagged" => "boolean|required"

        ];


        if ($isTagged) {
            $rules["recipients.*"] .= "required|email|distinct:strict|exists:users,email";

            $rules['body'] = ['required', 'string', function ($attribute, $value, $fail) {
                if (strpos($value, '@') === false) {
                    $fail('Le corps du mail doit contenir "@" quand le mail est tagué');
                }
            }];
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'recipients.required' => 'La liste des destinataires du mail est obligatoire.',
            'subject.required' => 'Le sujet du mail est obligatoire.',
            'body.required' => 'Le corps du mail est obligatoire.',
            'subject.string' => 'Le sujet du mail doit être une chaîne de caractères.',
            'body.string' => 'Le corps du mail doit être une chaîne de caractères.',
            'recipients.array' => 'La liste des destinataires du mail doit être un tableau.',
            'recipients.*.email' => 'La liste des destinataires du mail contient une adresse e-mail incorrecte.',
            'recipients.*.distinct' => 'La liste des destinataires du mail contient des doublons.',
            'recipients.*.exists' => "Le mail est marqué d'un tag, mais la liste des destinataires contient une adresse email qui n'appartient pas à un client.",
            'cc.array' => 'La liste des destinataires en copie du mail doit être un tableau.',
            'cc.*.email' => 'La liste des destinataires en copie du mail contient une adresse e-mail incorrecte.',
            'cc.*.distinct' => 'La liste des destinataires en copie du mail contient des doublons.',
            'attachments.array' => 'Les pièces jointes doivent être un tableau.',
            'attachments.*.file' => 'Les pièces jointes doivent être des fichiers de type PDF, JPEG, PNG, DOC, d\'une taille maximale de 12 Mo.',
            "is_tagged.required" => "Veuillez précisez  si ce mail est tagué ou non"
        ];
    }
}
