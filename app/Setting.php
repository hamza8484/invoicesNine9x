<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage; // For logo URL accessor

class Setting extends Model
{

    protected $fillable = [
        'company_name',
        'commercial_register',
        'tax_number',
        'email',
        'phone',
        'address',
        'logo', // Store logo path here
    ];

    // Accessor to get the full URL of the logo
    public function getLogoUrlAttribute()
    {
        return $this->logo ? Storage::url($this->logo) : null;
    }
}

