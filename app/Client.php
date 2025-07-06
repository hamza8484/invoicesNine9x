<?php

namespace App; 

use Illuminate\Database\Eloquent\Model;
use App\Project;
use App\Contract;

class Client extends Model
{
    protected $table = 'clients';

    protected $fillable = [
        'name',
        'company',
        'Vat_No',
        'email',
        'phone',
        'address',
        'notes',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class, 'client_id'); 
    }

    /**
     * Get the contracts for the client.
     */
    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    // إضافة هذه العلاقة: كل عميل يمكن أن يكون لديه العديد من الفواتير
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
    
}