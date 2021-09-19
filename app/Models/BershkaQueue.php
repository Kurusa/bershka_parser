<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BershkaQueue extends Model {

    protected $table = 'bershka_queue';
    protected $fillable = ['url', 'is_parsed'];

}