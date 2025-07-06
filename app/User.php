<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use App\Project;
use App\Expense;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function managedProjects()
    {
        return $this->hasMany(Project::class, 'manager_id');
    }

    // علاقة المستخدم مع المشاريع التي يعمل عليها (متعدد لمتعدد) عبر جدول project_user
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_user') // اسم الجدول المحوري
                    ->withPivot('role_in_project', 'assigned_at') // الأعمدة الإضافية في الجدول المحوري
                    ->withTimestamps(); // لإدارة created_at و updated_at في الجدول المحوري
    }

    /**
     * Get the tasks assigned to the user.
     */
    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    /**
     * Get the expenses created by the user.
     */
    public function createdExpenses()
    {
        return $this->hasMany(Expense::class, 'created_by');
    }
}
