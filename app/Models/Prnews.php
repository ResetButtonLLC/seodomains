<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prnews extends Model {

    public $table = "prnews";
    
    protected $fillable = [
        'id', 'url', 'price', 'audience', 'created_at', 'updated_at', 'domain_id'
    ];

}
