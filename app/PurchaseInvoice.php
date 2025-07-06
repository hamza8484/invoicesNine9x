<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; // لاستخدام Carbon لتنسيق التواريخ

class PurchaseInvoice extends Model
{

    protected $fillable = [
        'supplier_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'subtotal',
        'discount',
        'tax',
        'total',
        'paid_amount',
        'due_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
    ];

    // العلاقة مع المورد
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // العلاقة مع بنود فاتورة الشراء
    public function items()
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    // <== تأكد من وجود هذه العلاقة تماماً كما هي
    public function payments()
    {
        return $this->hasMany(PurchasePayment::class);
    }

    // Accessor لتنسيق تاريخ الإصدار
    public function getIssueDateFormattedAttribute()
    {
        return $this->issue_date ? Carbon::parse($this->issue_date)->format('Y-m-d') : null;
    }

    // Accessor لتنسيق تاريخ الاستحقاق
    public function getDueDateFormattedAttribute()
    {
        return $this->due_date ? Carbon::parse($this->due_date)->format('Y-m-d') : null;
    }

    // Accessor لاسم الحالة المعروض
    public function getStatusNameAttribute()
    {
        switch ($this->status) {
            case 'unpaid':
                return 'غير مدفوعة';
            case 'paid':
                return 'مدفوعة بالكامل';
            case 'partial':
                return 'مدفوعة جزئياً';
            default:
                return 'غير معروف';
        }
    }
}
