<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class InvoiceItem extends Model
{
    

    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'total', // هذا الحقل سيتم حسابه
    ];

     // **هذه هي الطريقة الصحيحة لـ Casting البيانات في PHP 7.2**
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'float',
        'total' => 'float',
    ];

    // العلاقة مع الفاتورة الأم (كل بند ينتمي لفاتورة واحدة)
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    
}