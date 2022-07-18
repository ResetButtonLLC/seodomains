<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class StockDomain extends Model
{
    public $timestamps = true;
    public $incrementing = false;
    protected $guarded = [];
}