<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\User;


class ActivityLog extends Model
{
    
    // بما أن هذا جدول سجلات، فغالباً لن يتم تعبئته جماعياً من الواجهة الأمامية
    // ولكن إذا كنت تقوم بذلك برمجياً، فستحتاج إلى fillable
    protected $fillable = [
        'user_id',
        'action',
        'target_type',
        'target_id',
        'description',
    ];

    // العلاقة: سجل النشاط ينتمي إلى مستخدم واحد
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // العلاقة البوليمورفية (Polymorphic Relationship)
    // تسمح لـ target_type و target_id بالإشارة إلى موديلات مختلفة
    public function target()
    {
        return $this->morphTo();
    }
}