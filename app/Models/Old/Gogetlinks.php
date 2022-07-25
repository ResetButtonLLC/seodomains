<?php

namespace App\Models\Old;

use Illuminate\Database\Eloquent\Model;

class Gogetlinks extends Model {

    protected $fillable = [
        'id', 'domain_id', 'placement_price', 'traffic', 'created_at', 'updated_at'
    ];

}
