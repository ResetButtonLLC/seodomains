<?php

namespace App\Models\Old;

use Illuminate\Database\Eloquent\Model;

class Rotapost extends Model {

    public $table = "rotapost";
    
    protected $fillable = [
        'id', 'placement_price', 'writing_price', 'theme', 'google_index', 'created_at', 'updated_at', 'domain_id'
    ];

}
