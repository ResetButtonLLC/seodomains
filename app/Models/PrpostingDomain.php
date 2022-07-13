<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PrpostingDomain extends StockDomain
{
    use HasFactory;

    protected $fillable = [
        'id', 'name', 'price', 'theme', 'traffic', 'domain_id', 'created_at', 'updated_at'
    ];
}
