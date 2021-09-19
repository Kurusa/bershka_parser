<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BershkaProducts extends Model {

    protected $table = 'bershka_products';
    protected $fillable = ['bershka_queue_id', 'reference', 'title', 'price', 'lining', 'description'];

}