<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage; // For image URL accessor

class Material extends Model
{

    protected $fillable = [
        'name',
        'code',
        'unit_id',
        'material_group_id',
        'tax_id',
        'purchase_price',
        'sale_price',
        'stock_quantity',
        'image', // Store image path here
        'description',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'stock_quantity' => 'integer',
    ];

    // Relationship with Unit
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    // Relationship with MaterialGroup
    public function materialGroup()
    {
        return $this->belongsTo(MaterialGroup::class);
    }

    // Relationship with Tax
    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    // Accessor to get the full URL of the image
    public function getImageUrlAttribute()
    {
        return $this->image ? Storage::url($this->image) : null;
    }
}

