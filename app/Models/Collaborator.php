<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collaborator extends Model
{
    public $table = "collaborator";

    protected $fillable = [
        'id', 'site_id', 'url', 'price', 'theme', 'traffic', 'created_at', 'updated_at', 'domain_id'
    ];
}
