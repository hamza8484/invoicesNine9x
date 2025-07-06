<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialGroup extends Model
{

    protected $fillable = [
        'name',
        'description',
    ];

    // يمكنك إضافة أي علاقات مستقبلية هنا، مثلاً إذا كانت مجموعة الأصناف مرتبطة بمنتجات أو مواد
}
