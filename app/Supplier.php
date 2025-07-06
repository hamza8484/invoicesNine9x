<?php

namespace App; // أو App إذا كنت لا تستخدم مجلد Models

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Expense;



class Supplier extends Model
{
   

    // اسم الجدول الذي يرتبط به هذا النموذج (تأكد أنه 'suppliers' كما في المهاجر)
    protected $table = 'suppliers';

    // الحقول التي يسمح بتعبئتها جماعيًا (Mass Assignment)
    protected $fillable = [
        'name',
        'vat_No', // تأكد من مطابقة حالة الأحرف هنا 'vat_No'
        'company',
        'contact_person',
        'phone',
        'email',
        'address',
        'category',
    ];

    // إذا كنت تفضل السماح بتعبئة أي حقل مؤقتًا (للتطوير السريع فقط)
    // يمكنك استخدام: protected $guarded = [];
    // ولكن يفضل تحديد fillable للحفاظ على الأمان.
    // protected $guarded = [];

    /**
     * Get the expenses associated with the supplier.
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}