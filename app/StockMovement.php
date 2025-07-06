<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{

    protected $fillable = [
        'material_id',
        'warehouse_id',
        'transaction_type',
        'quantity',
        'unit_cost',
        'total_cost',
        'transaction_date',
        'reference_type',
        'reference_id',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    // Relationship with Material
    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    // Relationship with Warehouse
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    // Relationship with User (who performed the movement)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Polymorphic relationship for reference_type and reference_id
    public function reference()
    {
        return $this->morphTo();
    }

    /**
     * Get the display name for the transaction type.
     *
     * @return string
     */
    public function getTransactionTypeNameAttribute()
    {
        switch ($this->transaction_type) {
            case 'purchase_receipt': return 'استلام شراء';
            case 'sale_issue': return 'صرف بيع';
            case 'transfer_out': return 'تحويل خارج';
            case 'transfer_in': return 'تحويل داخل';
            case 'adjustment_in': return 'تسوية بالزيادة';
            case 'adjustment_out': return 'تسوية بالنقصان';
            case 'project_issue': return 'صرف لمشروع';
            case 'project_return': return 'إرجاع من مشروع';
            default: return $this->transaction_type;
        }
    }

    /**
     * Determine if the movement increases stock.
     *
     * @return bool
     */
    public function isIncrease()
    {
        return in_array($this->transaction_type, ['purchase_receipt', 'transfer_in', 'adjustment_in', 'project_return']);
    }

    /**
     * Determine if the movement decreases stock.
     *
     * @return bool
     */
    public function isDecrease()
    {
        return in_array($this->transaction_type, ['sale_issue', 'transfer_out', 'adjustment_out', 'project_issue']);
    }
}
