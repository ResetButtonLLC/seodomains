<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gogetlinks extends Model {

    use SoftDeletes;

    protected $fillable = [
        'id', 'domain_id', 'placement_price', 'traffic', 'created_at', 'updated_at' , 'deleted_at'
    ];

}
