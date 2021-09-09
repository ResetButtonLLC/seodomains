<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Miralinks extends Model {

    protected $fillable = [
        'id', 'name', 'site_id', 'desc', 'placement_price', 'writing_price', 'placement_price_rur', 'writing_price_rur', 'region', 'theme', 'google_index', 'links', 'lang', 'traffic', 'created_at', 'updated_at', 'domain_id',
    ];

}
