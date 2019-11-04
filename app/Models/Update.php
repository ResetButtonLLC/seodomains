<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Update extends Model
{
    public $timestamps = false;

    protected $fillable = ['name','updated_at'];

    public static function setLinkExchangeUpdated ($link_exchange_name)
    {
        self::updateOrCreate(
            ['name' => $link_exchange_name],
            ['updated_at' => Carbon::now()]
        );
    }


}
