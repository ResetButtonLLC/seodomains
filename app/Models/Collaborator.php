<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collaborator extends Model
{
    public $table = "collaborator";

    public $incrementing = false;

    protected $fillable = [
        'id', 'url', 'price', 'theme', 'traffic', 'domain_id', 'created_at', 'updated_at'
    ];
}
