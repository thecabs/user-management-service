<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BankingClientRequest extends FormRequest
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
            'numero_compte_bancaire' => 'required|string|max:50',
            'telephone_cbs' => 'required|string|max:20',
            'solde_min_declare' => 'required|numeric|min:0',
            'solde_max_declare' => 'required|numeric|min:0|gte:solde_min_declare',
            'recommande_par_tel' => 'nullable|string|max:20',
            'region' => 'required|string|max:255',
            'ville' => 'required|string|max:255',
            'quartier' => 'required|string|max:255',
            'telephone_recuperation' => 'nullable|string|max:20',
            'email_recuperation' => 'nullable|email',
        ];
    }

    public function messages(): array
    {
        return [
            'solde_max_declare.gte' => 'Le solde maximum doit être supérieur ou égal au solde minimum',
            'numero_compte_bancaire.required' => 'Le numéro de compte bancaire est obligatoire pour les clients bancaires',
            'telephone_cbs.required' => 'Le téléphone enregistré dans le CBS est obligatoire',
        ];
    }
}