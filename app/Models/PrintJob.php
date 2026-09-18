<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class PrintJob extends Model
{
    use \App\Models\Concerns\HasActiveBranch;

    protected $fillable = [
        'branch_id',
        'order_id',
        'preparation_station_id',
        'printer_name',
        'detail_ids',
        'correction_ids',
        'is_correction',
        'status',
        'attempts',
        'error',
        'confirmed_at',
    ];

    protected $casts = [
        'detail_ids' => 'array',
        'correction_ids' => 'array',
        'is_correction' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function preparationStation()
    {
        return $this->belongsTo(PreparationStation::class);
    }

    public function payload(bool $reprint = false): array
    {
        return [
            'id' => $this->id,
            'printer_name' => $this->printer_name,
            'url' => URL::temporarySignedRoute('orders.kitchen-print', now()->addMinutes(5), [
                'id' => $this->order_id,
                'detail_ids' => $this->detail_ids ?? [],
                'correction' => $this->is_correction,
                'correction_ids' => $this->correction_ids ?? [],
                'reprint' => $reprint,
            ]),
        ];
    }
}
