<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{
    Model,
    Builder,
    Relations\HasOne
};

class Domains extends Model {

    public $timestamps = ['created_at'];
    protected $fillable = [
        'id', 'url', 'created_at', 'majestic_tf', 'majestic_cf', 'majestic_updated'
    ];

    public function miralinks(): HasOne {
        return $this->hasOne('App\Models\Miralinks', 'domain_id', 'id');
    }
    
    public function gogetlinks(): HasOne {
        return $this->hasOne('App\Models\Gogetlinks', 'domain_id', 'id');
    }
    
    public function sape(): HasOne {
        return $this->hasOne('App\Models\Sape', 'domain_id', 'id');
    }
    
    public function rotapost(): HasOne {
        return $this->hasOne('App\Models\Rotapost', 'domain_id', 'id');
    }

}
