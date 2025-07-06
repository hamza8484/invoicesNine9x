<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array', // سيقوم Laravel بتحويل النص إلى مصفوفة/JSON تلقائياً
        'new_values' => 'array', // سيقوم Laravel بتحويل النص إلى مصفوفة/JSON تلقائياً
    ];

    // Relationship with User (who performed the action)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Polymorphic relationship to the audited model
    public function auditable()
    {
        return $this->morphTo();
    }
}
