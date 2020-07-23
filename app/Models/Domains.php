<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{
    Model,
    Builder,
    Relations\HasOne
};
use Illuminate\Database\Eloquent\SoftDeletes;

class Domains extends Model {

    public $timestamps = ['created_at'];
    const UPDATED_AT = null;
    use SoftDeletes;

    protected $guarded = [];

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
    
    public function prnews(): HasOne {
        return $this->hasOne('App\Models\Prnews', 'domain_id', 'id');
    }

}
