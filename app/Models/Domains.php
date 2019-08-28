<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Domains extends Model {

    public $timestamps = false;
    protected $fillable = [
        'id', 'url', 'name', 'placement_price', 'writing_price', 'region', 'theme', 'google_index', 'links', 'language', 'traffic', 'source', 'created_at'
    ];

}
