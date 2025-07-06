<?php

namespace App; // أو App إذا كنت لا تستخدم مجلد Models

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Project; // استيراد نموذج Project

class Income extends Model
{
   
    protected $table = 'incomes'; // تأكد من اسم الجدول

    protected $fillable = [
        'project_id',
        'source',
        'amount',
        'income_date',
        'notes',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'income_date' => 'date',
    ];

    /**
     * Get the project that the income belongs to.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}