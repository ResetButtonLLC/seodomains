<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collaborator extends StockDomain
{
    public $table = "collaborator";

    protected $fillable = [
        'id', 'url', 'price', 'theme', 'traffic', 'domain_id', 'created_at', 'updated_at'
    ];
}
