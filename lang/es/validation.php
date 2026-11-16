<?php

// Solo los mensajes que usa el CRM. Para el resto, publica los ficheros de idioma completos.
return [
    'required' => 'Rellena :attribute.',
    'integer' => ':Attribute tiene que ser un número entero.',
    'min' => ['numeric' => ':Attribute tiene que ser como mínimo :min.'],
    'max' => ['numeric' => ':Attribute no puede pasar de :max.', 'string' => ':Attribute no puede tener más de :max caracteres.'],
    'unique' => 'Ya existe un registro con esa :attribute.',
    'in' => 'Elige :attribute de la lista.',
    'enum' => 'Elige :attribute de la lista.',
    'email' => 'Ese email no es válido.',
    'date' => ':Attribute no es una fecha válida.',
    'after' => ':Attribute tiene que ser posterior a ahora.',
    'exists' => 'Elige :attribute de la lista.',

    'attributes' => [
        'reference' => 'la referencia',
        'title' => 'el título',
        'operation' => 'la operación',
        'type' => 'el tipo',
        'zone' => 'la zona',
        'price' => 'el precio',
        'bedrooms' => 'los dormitorios',
        'area' => 'la superficie',
        'name' => 'el nombre',
        'max_price' => 'el precio máximo',
        'min_bedrooms' => 'los dormitorios mínimos',
        'min_area' => 'la superficie mínima',
        'contact_id' => 'el cliente',
        'scheduled_at' => 'la fecha de la visita',
    ],
];
