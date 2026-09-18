<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use \App\Models\Concerns\HasActiveBranch;
    public const ORDER_TYPES = ['dine_in', 'pickup', 'delivery'];

    protected $fillable = [
        'table_id',
        'customer_id',
        'user_id',
        'order_type',
        'customer_name',
        'customer_phone',
        'delivery_address',
        'status',
        'total',
        'amount_pending',
        'offline_token',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function joinedTables()
    {
        return $this->belongsToMany(Table::class, 'order_table')->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function corrections()
    {
        return $this->hasMany(OrderCorrection::class);
    }

    public function printJobs()
    {
        return $this->hasMany(PrintJob::class);
    }

    public function isReadyForCheckout(): bool
    {
        if ($this->status !== 'abierto') {
            return false;
        }

        $activeDetails = $this->details()
            ->where('cooking_status', '!=', 'cancelled')
            ->get();

        if ($activeDetails->isEmpty()) {
            return false;
        }

        return $activeDetails
            ->where('requires_kitchen', true)
            ->every(fn (OrderDetail $detail) => $detail->cooking_status === 'served');
    }

    public function getIsReadyForCheckoutAttribute(): bool
    {
        return $this->isReadyForCheckout();
    }

    public function sale()
    {
        return $this->hasOne(Sale::class);
    }

    public function releaseTables(): void
    {
        $tableIds = $this->joinedTables()
            ->pluck('tables.id')
            ->push($this->table_id)
            ->filter()
            ->unique();

        Table::query()->whereKey($tableIds)->update(['status' => 'libre']);
        $this->joinedTables()->detach();
    }

    public function getOrderTypeLabelAttribute(): string
    {
        return match ($this->order_type) {
            'pickup' => 'Retiro',
            'delivery' => 'Delivery',
            default => 'Salón',
        };
    }

    public function getServiceLabelAttribute(): string
    {
        if ($this->order_type !== 'dine_in') {
            return $this->order_type_label;
        }

        $tables = collect([$this->getRelationValue('table')])
            ->filter()
            ->merge($this->relationLoaded('joinedTables') ? $this->joinedTables : $this->joinedTables()->get())
            ->pluck('name')
            ->unique()
            ->values();

        return $tables->isEmpty() ? 'Mesa sin asignar' : $tables->join(' + ');
    }
}
