<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class SignUpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function messages() : array{
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El e-mail es obligatorio.',
            'email.email' => 'Debe ingresar un e-mail válido.',
            'email.unique' => 'El correo ya se encuentra registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas deben ser iguales.',
            'password.min' => 'La contraseña debe tener por lo menos :min carácteres.',
            'password.letters' => 'La contraseña debe tener por lo menos 1 letra.',
            'password.mixedCase' => 'La contraseña debe tener por lo menos 1 letra máyuscula y 1 mínuscula.',
            'password.symbols' => 'La contraseña debe tener por lo menos 1 carácter (@$%&_-).',
            'password.numbers' => 'La contraseña debe tener por lo menos 1 número.',
            'password.compromised' => 'La contraseña es poco segura. Ingrese una más segura.'
        ];
    }
 
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', 
                Password::min(8)
                        ->letters()
                        ->mixedCase()
                        ->symbols()
                        ->numbers()
                        ->uncompromised()
            ]
        ];
    }
}
