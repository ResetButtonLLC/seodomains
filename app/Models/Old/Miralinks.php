<?php

namespace App\Models\Old;

use Illuminate\Database\Eloquent\Model;

class Miralinks extends Model {

    protected $fillable = [
        'id', 'name', 'site_id', 'desc', 'placement_price', 'writing_price', 'placement_price_rur', 'writing_price_rur', 'region', 'theme', 'google_index', 'links', 'lang', 'traffic', 'created_at', 'updated_at', 'domain_id', 'last_placement', 'placement_time'
    ];

}
