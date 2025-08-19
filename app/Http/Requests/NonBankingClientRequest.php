<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NonBankingClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'date_naissance' => 'required|date|before:today',
            'email' => 'required|email|unique:users,email',
            'telephone' => 'required|string|unique:users,telephone',
            'type_piece' => 'required|in:cni,passeport,recu_carte_nationale',
            'numero_piece_identite' => 'required|string|max:50',
            'niu' => 'nullable|string|max:50',
            'date_expiration_piece' => 'required|date|after:today',
            'piece_face_avant' => 'required|image|mimes:jpeg,jpg,png|max:5120',
            'piece_face_arriere' => 'required|image|mimes:jpeg,jpg,png|max:5120',
            'selfie' => 'required|image|mimes:jpeg,jpg,png|max:2048',
            'recommande_par_tel' => 'nullable|string|max:20',
            'region' => 'required|string|max:255',
            'ville' => 'required|string|max:255',
            'quartier' => 'required|string|max:255',
            'telephone_recuperation' => 'nullable|string|max:20',
            'email_recuperation' => 'nullable|email',
        ];
    }
}