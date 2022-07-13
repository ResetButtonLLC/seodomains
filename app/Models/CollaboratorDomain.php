<?php

namespace App\Models;

class CollaboratorDomain extends StockDomain
{
    protected $fillable = [
        'id', 'domain', 'price', 'theme', 'traffic', 'domain_id', 'created_at', 'updated_at'
    ];
}
