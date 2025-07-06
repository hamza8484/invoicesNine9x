<?php

namespace App; // أو App إذا كنت لا تستخدم مجلد Models

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Project; // استيراد نموذج Project
use App\Supplier; // استيراد نموذج Supplier
use App\User;    // استيراد نموذج User

class Expense extends Model
{
   

    protected $table = 'expenses'; // تأكد من اسم الجدول

    protected $fillable = [
        'project_id',
        'supplier_id',
        'created_by',
        'type',
        'description',
        'amount',
        'expense_date',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'type' => 'string',
    ];

    /**
     * Get the project that the expense belongs to.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the supplier that the expense is associated with.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the user who created the expense.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}