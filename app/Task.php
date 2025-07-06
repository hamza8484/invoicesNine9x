<?php

namespace App; // أو App إذا كنت تستخدم Laravel 7 بدون مجلد Models

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Project; // استيراد نموذج Project
use App\User;    // استيراد نموذج User

class Task extends Model
{
   

    protected $table = 'tasks'; // تأكد من اسم الجدول

    protected $fillable = [
        'project_id',
        'assigned_to',
        'title',
        'description',
        'status',
        'priority',
        'start_date',
        'end_date',
        'completed_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'completed_at' => 'datetime', // يمكن أن يكون datetime لتسجيل الوقت أيضًا
        'status' => 'string',
        'priority' => 'string',
    ];

    /**
     * Get the project that owns the task.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user that the task is assigned to.
     */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}