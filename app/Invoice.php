<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Attachment;


class Invoice extends Model
{
   

    // تحديد الحقول التي يمكن تعبئتها جماعيا (mass assignable)
    protected $fillable = [
        'project_id',
        'client_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'subtotal',
        'discount',
        'tax',
        'total',
        'paid_amount',
        'due_amount',
        'payment_method',
        'status',
        'notes',
    ];

    // تحديد الحقول التي يجب تحويلها إلى كائنات Carbon (تواريخ)
    protected $dates = [
        'issue_date',
        'due_date',
    ];

     // **هذه هي الطريقة الصحيحة لـ Casting البيانات المالية في PHP 7.2**
    protected $casts = [
        'subtotal' => 'float',
        'discount' => 'float',
        'tax' => 'float',
        'total' => 'float',
        'paid_amount' => 'float',
        'due_amount' => 'float',
    ];

    // // إذا كنت تستخدم Laravel 9+ و Carbon تلقائياً في Migration، قد لا تحتاج لـ $dates يدوياً.
    // // ولكن إضافتها لا تضر وتضمن التحويل الصحيح.

    // العلاقة مع جدول المشاريع (كل فاتورة تنتمي لمشروع واحد)
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // العلاقة مع جدول العملاء (كل فاتورة تنتمي لعميل واحد)
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // العلاقة مع جدول بنود الفواتير (كل فاتورة تحتوي على العديد من البنود)
    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

     // علاقة: الفاتورة لديها العديد من المدفوعات
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    // // مثال على Accessor/Mutator لـ `total` إذا كنت تريد أن يكون محسوباً دائماً عند الجلب
    // // هذا يعتمد على كيفية تخطيطك لإدارة الحسابات. عادة ما يتم الحساب في المتحكم قبل الحفظ.
    // // ولكن يمكن تعريفها هنا إذا كان هناك منطق معقد أو ترغب في حسابها ديناميكياً.
    // protected function total(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn ($value) => $this->subtotal - $this->discount + $this->tax,
    //     );
    // }

    
}