<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; // لاستخدام Carbon لتنسيق الوقت

class Timesheet extends Model
{

    protected $fillable = [
        'user_id',
        'project_id',
        'task_id',
        'work_date',
        'start_time',
        'end_time',
        'duration', // Calculated in controller
        'notes',
    ];

    protected $casts = [
        'work_date' => 'date',
        'start_time' => 'datetime', // Cast to datetime for easier Carbon operations
        'end_time' => 'datetime',   // Cast to datetime for easier Carbon operations
        'duration' => 'decimal:2',
    ];

    // Relationship with User (Employee)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship with Project
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // Relationship with Task (Optional)
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Accessor for formatted duration (e.g., "8.5 hours").
     *
     * @return string
     */
    public function getDurationFormattedAttribute()
    {
        if ($this->duration) {
            $hours = floor($this->duration);
            $minutes = round(($this->duration - $hours) * 60);
            return sprintf('%d ساعة و %d دقيقة', $hours, $minutes);
        }
        return '0 ساعة';
    }
}
