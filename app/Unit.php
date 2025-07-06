<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{

    protected $fillable = [
        'name',
        'symbol',
        'description',
    ];

    // يمكنك إضافة أي علاقات مستقبلية هنا، مثلاً إذا كانت الوحدة مرتبطة بمنتجات
}