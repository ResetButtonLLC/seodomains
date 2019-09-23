<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sape extends Model {
    
    public $table = "sape";

    protected $fillable = [
        'id', 'placement_price', 'google_index', 'created_at', 'updated_at', 'domain_id'
    ];

}
