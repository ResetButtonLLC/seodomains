<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Domains extends Model {

    public $timestamps = ['created_at'];
    protected $fillable = [
        'id', 'url', 'name', 'site_id', 'placement_price', 'writing_price', 'region', 'theme', 'google_index', 'links', 'language', 'traffic', 'source', 'created_at'
    ];

}
