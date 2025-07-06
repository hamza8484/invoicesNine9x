<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{

    protected $fillable = [
        'attachable_type', // اسم الكلاس الكامل للنموذج المرتبط (مثل App\Models\Invoice)
        'attachable_id',   // ID الكائن المرتبط
        'file_path',       // المسار الكامل للملف المخزن
        'file_name',       // الاسم الأصلي للملف (اختياري)
        'uploaded_by',     // ID المستخدم الذي قام بالرفع
    ];

    // علاقة بوليمورفية: المرفق ينتمي إلى كائن معين
    public function attachable()
    {
        return $this->morphTo();
    }

    // علاقة: المرفق تم رفعه بواسطة مستخدم معين
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // دالة مساعدة للحصول على رابط تنزيل الملف
    public function getDownloadUrlAttribute()
    {
        // تأكد أنك قمت بتشغيل php artisan storage:link
        // وأن الملفات مخزنة في storage/app/public
        return asset('storage/' . $this->file_path);
    }
}