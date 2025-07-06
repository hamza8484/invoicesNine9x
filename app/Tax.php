<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{

    protected $fillable = [
        'name',
        'rate',
        'type',
        'description',
    ];

    protected $casts = [
        'rate' => 'decimal:2', // للتأكد من التعامل مع النسبة العشرية بدقة
    ];

    // يمكنك إضافة أي علاقات مستقبلية هنا، مثلاً إذا كانت الضريبة مرتبطة بفواتير معينة
}
