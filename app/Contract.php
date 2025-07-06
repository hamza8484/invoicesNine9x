<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Project; // استيراد نموذج Project
use App\Client;  // استيراد نموذج Client

class Contract extends Model
{
    

    protected $table = 'contracts'; // تأكد من اسم الجدول

    protected $fillable = [
        'project_id',
        'client_id',
        'contract_number',
        'start_date',
        'end_date',
        'total_amount',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_amount' => 'decimal:2',
        'status' => 'string',
    ];

    /**
     * Get the project that owns the contract.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the client that owns the contract.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}