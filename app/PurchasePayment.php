<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; // لاستخدام Carbon لتنسيق التواريخ

class PurchasePayment extends Model
{

    protected $fillable = [
        'purchase_invoice_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'payment_date' => 'date',
    ];

    // العلاقة مع فاتورة الشراء
    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

     public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessor لتنسيق تاريخ الدفع
    public function getPaymentDateFormattedAttribute()
    {
        return $this->payment_date ? Carbon::parse($this->payment_date)->format('Y-m-d') : null;
    }

    // Accessor لاسم طريقة الدفع المعروض
    public function getPaymentMethodNameAttribute()
    {
        $methods = [
            'cash' => 'نقداً',
            'bank_transfer' => 'تحويل بنكي',
            'cheque' => 'شيك',
            'card' => 'بطاقة ائتمان',
            'other' => 'أخرى',
        ];
        return $methods[$this->payment_method] ?? $this->payment_method;
    }
}
