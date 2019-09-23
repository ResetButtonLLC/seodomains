<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gogetlinks extends Model {

    protected $fillable = [
        'id', 'placement_price', 'traffic', 'created_at', 'updated_at', 'domain_id'
    ];

}
