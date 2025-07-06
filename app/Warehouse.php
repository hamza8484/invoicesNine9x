<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{

    protected $fillable = [
        'name',
        'code',
        'location',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean', // Ensures is_active is treated as boolean
    ];

    // يمكنك إضافة علاقات مستقبلية هنا، مثلاً مع جدول المخزون (Inventory)
    // public function inventoryItems()
    // {
    //     return $this->hasMany(Inventory::class);
    // }
}
