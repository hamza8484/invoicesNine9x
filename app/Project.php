<?php

namespace App; 

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Client; // تأكد من استيراد نموذج Client
use App\User;   // تأكد من استيراد نموذج User
use App\Contract;
use App\Expense; 
use App\Income;
use App\Invoice;
use App\Task;

class Project extends Model
{
    
    // اسم الجدول إذا كان مختلفًا عن اسم النموذج بصيغة الجمع
    protected $table = 'projects';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_name',
        'description',
        'start_date',
        'end_date',
        'actual_start_date',
        'actual_end_date',
        'budget',
        'current_spend',
        'total_income',
        'status',
        'client_id',
        'manager_id',
        'location',
        'contract_value',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
        'budget' => 'decimal:2',
        'current_spend' => 'decimal:2',
        'total_income' => 'decimal:2',
        'contract_value' => 'decimal:2',
        'status' => 'string', // أو يمكنك استخدام Eloquent Enums إذا كنت تستخدم Laravel 9+
    ];


    /**
     * Get the client that owns the project.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the manager (user) that manages the project.
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id'); // manager_id هو المفتاح الأجنبي
    }

    // علاقة المشروع مع المستخدمين (متعدد لمتعدد) عبر جدول project_user
    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user') // اسم الجدول المحوري
                    ->withPivot('role_in_project', 'assigned_at') // الأعمدة الإضافية في الجدول المحوري
                    ->withTimestamps(); // لإدارة created_at و updated_at في الجدول المحوري
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the contracts for the project.
     */
    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Get the expenses for the project.
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get the incomes for the project.
     */
    public function incomes()
    {
        return $this->hasMany(Income::class);
    }

    // إضافة هذه العلاقة: كل مشروع يمكن أن يكون لديه العديد من الفواتير
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    
    
}