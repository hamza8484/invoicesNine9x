<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceItem extends Model
{

    protected $fillable = [
        'purchase_invoice_id',
        'material_id',
        'quantity',
        'unit_price',
        'total',
    ];

    // العلاقة مع فاتورة الشراء
    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    // العلاقة مع المادة
    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
