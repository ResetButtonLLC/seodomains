<?php

namespace App\Models\Old;

use Illuminate\Database\Eloquent\Model;

class Sape extends Model {

    public $table = "sape";

    protected $fillable = [
        'id', 'domain_id', 'placement_price', 'google_index', 'created_at', 'updated_at'
    ];

}
