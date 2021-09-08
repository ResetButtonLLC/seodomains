<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{
    Model,
    Builder,
    Relations\HasOne
};
use Illuminate\Support\Facades\DB;

class Domains extends Model {

    public $timestamps = ['created_at'];
    const UPDATED_AT = null;

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

    public function collaborator(): HasOne {
        return $this->hasOne('App\Models\Collaborator', 'domain_id', 'id');
    }

    public static function getDomainsForExport() {
        $domains = Domains::leftjoin('gogetlinks', 'domains.id', '=', 'gogetlinks.domain_id')
            ->leftjoin('miralinks', 'domains.id', '=', 'miralinks.domain_id')
            ->leftjoin('prnews', 'domains.id', '=', 'prnews.domain_id')
            ->leftjoin('rotapost', 'domains.id', '=', 'rotapost.domain_id')
            ->leftjoin('sape', 'domains.id', '=', 'sape.domain_id')
            ->leftjoin('collaborator', 'domains.id', '=', 'collaborator.domain_id')
            //->select('domains.*', 'gogetlinks.placement_price as gogetlinks_placement_price','miralinks.placement_price as miralinks_placement_price','prnews.price as prnews_placement_price','rotapost.placement_price as rotapost_placement_price','sape.placement_price as sape_placement_price')
            ->select(
                'domains.*',
                'gogetlinks.placement_price as gogetlinks_placement_price',
                'gogetlinks.domain_id as gogetlinks_domain_id',
                'miralinks.placement_price as miralinks_placement_price',
                'miralinks.site_id as miralinks_site_id',
                'miralinks.writing_price as miralinks_writing_price',
                'miralinks.theme as miralinks_theme',
                'miralinks.google_index as miralinks_google_index',
                'miralinks.links as miralinks_links',
                'miralinks.lang as miralinks_lang',
                'miralinks.desc as miralinks_desc',
                'prnews.price as prnews_placement_price',
                'prnews.audience as prnews_audience',
                'collaborator.price as collaborator_placement_price',
                'collaborator.site_id as collaborator_site_id',
                'rotapost.placement_price as rotapost_placement_price',
                'rotapost.writing_price as rotapost_writing_price',
                'sape.placement_price as sape_placement_price',
                'sape.domain_id as sape_domain_id'
                )
            ->where('domains.url', '<>', '')->get();

            //Сортировка сохраняет порядок $domains
            //Оставлю на память, но так не работает - если домена не существует, то пропуска не будет
            //->orderByRaw('FIELD(domains.url, '.'"'.implode('","',$domains).'"'.')')

        return $domains;
    }

}
