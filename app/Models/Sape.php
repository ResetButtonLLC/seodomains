<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sape extends Model {

    public $table = "sape";

    protected $fillable = [
        'id', 'domain_id', 'placement_price', 'google_index', 'created_at', 'updated_at'
    ];

}
