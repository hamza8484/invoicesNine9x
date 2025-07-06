<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
   
    protected $fillable = [
        'invoice_id',
        'user_id',
        'payment_date',
        'amount',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date', // لتحويل التاريخ إلى كائن Carbon
        'amount' => 'decimal:2', // للتأكد من التعامل مع العدد العشري بدقة
    ];

    // علاقة: الدفعة تنتمي إلى فاتورة واحدة
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // علاقة: الدفعة تم تسجيلها بواسطة مستخدم واحد (يمكن أن يكون فارغاً)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
