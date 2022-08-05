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
            ->leftjoin('collaborator_domains', 'domains.id', '=', 'collaborator_domains.domain_id')
            ->leftjoin('prnews_domains', 'domains.id', '=', 'prnews_domains.domain_id')
            ->leftjoin('prposting_domains', 'domains.id', '=', 'prposting_domains.domain_id')
            ->select(
                'domains.*',
                'collaborator_domains.price as collaborator_price',
                'collaborator_domains.id as collaborator_domain_id',
                'prnews_domains.price as prnews_price',
                'prnews_domains.id as prnews_domain_id',
                'prposting_domains.price as prposting_price',
                'prposting_domains.id as prposting_domain_id',
                )
            ->whereNull('domains.deleted_at')
            ->orderBy('domain')
            ->get();

        return $domains;
    }

}
