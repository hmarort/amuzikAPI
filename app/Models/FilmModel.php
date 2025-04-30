<?php

namespace App\Models;

use CodeIgniter\Model;

class FilmModel extends Model
{
    protected $primaryKey="filmId";

    protected $table="films";

    protected $allowedFields=["title", "genre", "country", "date", "poster", "oid"];

    protected $validationRules=[
		'title' => 'required',
        'genre' => 'required',
        'country' => 'required',
        'date' => 'date|required',
		'poster' => 'required',	
	];

    protected $validationMessages=[
        'title' => ['required'=>'Title is required.'],
        'genre' => ['required'=>'Genre is required.'],
        'country' => ['required'=>'Country is required.'],
        'date' =>[
            'date' => 'Not a valid date.',
            'required' => 'Date is required.'
        ],
        'poster' => ['required'=>'Poster is required.']
    ];

    public function __construct()
    {
        parent::__construct();
    }
    
}