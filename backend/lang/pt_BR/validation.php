<?php

return [
    'required' => 'O campo :attribute é obrigatório.',
    'string' => 'O campo :attribute deve ser um texto.',
    'email' => 'O campo :attribute deve ser um e-mail válido.',
    'unique' => 'O :attribute informado já está em uso.',
    'numeric' => 'O campo :attribute deve ser um número.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'enum' => 'O :attribute selecionado é inválido.',
    'date_format' => 'O campo :attribute deve ser uma data no formato AAAA-MM-DD.',
    'after_or_equal' => 'O campo :attribute deve ser uma data igual ou posterior a :date.',
    // O único uso de decimal no projeto é o valor em reais: 0 a 2 casas
    'decimal' => 'O campo :attribute deve ter no máximo duas casas decimais.',
    'gt' => [
        'numeric' => 'O campo :attribute deve ser maior que :value.',
    ],
    'max' => [
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'string' => 'O campo :attribute não pode ter mais de :max caracteres.',
    ],
    'min' => [
        'numeric' => 'O campo :attribute deve ser no mínimo :min.',
        'string' => 'O campo :attribute deve ter no mínimo :min caracteres.',
    ],
    'password' => [
        'letters' => 'O campo :attribute deve conter pelo menos uma letra.',
        'numbers' => 'O campo :attribute deve conter pelo menos um número.',
        'mixed' => 'O campo :attribute deve conter letras maiúsculas e minúsculas.',
        'symbols' => 'O campo :attribute deve conter pelo menos um símbolo.',
    ],

    // Nomes de campos que valem para o sistema inteiro
    'attributes' => [
        'name' => 'nome',
        'email' => 'e-mail',
        'password' => 'senha',
    ],
];
