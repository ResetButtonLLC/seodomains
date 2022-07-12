<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Update extends Model
{
    public $timestamps = false;

    protected $fillable = ['name','updated_at'];

    public static function setUpdatedTime ($stockName)
    {
        self::updateOrCreate(
            ['name' => $stockName],
            ['updated_at' => Carbon::now()]
        );
    }


}
