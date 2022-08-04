<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, Relations\HasOne};
use Illuminate\Database\Eloquent\SoftDeletes;

class Domain extends Model {

    use SoftDeletes;

    public $timestamps = ['created_at'];

    const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'ahrefs_updated_at' => 'datetime',
        'majestic_updated_at' => 'datetime',
        'traffic_updated_at' => 'datetime',
    ];

    public function collaborator(): HasOne {
        return $this->hasOne(CollaboratorDomain::class);
    }

    public function prnews(): HasOne {
        return $this->hasOne(PrnewsDomain::class);
    }

    public function prposting(): HasOne {
        return $this->hasOne(PrpostingDomain::class);
    }

    public static function getDomainsForExport() {


        $domains = Domain::query()
//            ->leftjoin('gogetlinks', 'domains.id', '=', 'gogetlinks.domain_id')
//            ->leftjoin('miralinks', 'domains.id', '=', 'miralinks.domain_id')
            ->leftjoin('prnews', 'domains.id', '=', 'prnews.domain_id')
//            ->leftjoin('rotapost', 'domains.id', '=', 'rotapost.domain_id')
//            ->leftjoin('sape', 'domains.id', '=', 'sape.domain_id')
            ->leftjoin('collaborator_domains', 'domains.id', '=', 'collaborator_domains.domain_id')
            //->select('domains.*', 'gogetlinks.placement_price as gogetlinks_placement_price','miralinks.placement_price as miralinks_placement_price','prnews.price as prnews_placement_price','rotapost.placement_price as rotapost_placement_price','sape.placement_price as sape_placement_price')
            ->select(
                'domains.*',
/*
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
                'miralinks.last_placement as miralinks_last_placement',
                'miralinks.placement_time as miralinks_placement_time',
                'rotapost.placement_price as rotapost_placement_price',
                'rotapost.writing_price as rotapost_writing_price',
                'sape.placement_price as sape_placement_price',
                'sape.domain_id as sape_domain_id'
*/
                'prnews.price as prnews_placement_price',
                'prnews.audience as prnews_audience',
                'collaborator_domains.price as collaborator_placement_price',
                'collaborator_domains.id as collaborator_site_id',
                )
            ->where('domains.domain', '<>', '')->get();

        return $domains;
    }

}
