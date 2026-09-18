<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderCorrection;
use App\Models\Payment;
use App\Models\PaymentEdit;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\TipAdjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $tipsEnabled = (bool) (Setting::first()?->tips_enabled ?? true);
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'type' => ['nullable', Rule::in(array_merge(['correction', 'payment', 'payment_edit'], $tipsEnabled ? ['tip'] : []))],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $corrections = OrderCorrection::query()
            ->whereIn('order_id', Order::query()->select('id'))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->select([
                'created_at as occurred_at',
                DB::raw("'correction' as event_type"),
                'action',
                'order_id as reference',
                'table_name as context',
                'product_name as subject',
                'notes',
                DB::raw('NULL as amount'),
                DB::raw('NULL as method'),
                DB::raw('NULL as actor'),
                DB::raw('NULL as previous_method'),
                DB::raw('NULL as previous_amount'),
            ]);

        $payments = Payment::query()
            ->join('sales', 'sales.id', '=', 'payments.sale_id')
            ->leftJoin('payment_methods', 'payment_methods.id', '=', 'payments.payment_method_id')
            ->leftJoin('users as cashiers', 'cashiers.id', '=', 'sales.cashier_id')
            ->whereIn('payments.sale_id', Sale::query()->select('id'))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('payments.created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('payments.created_at', '<=', $to))
            ->select([
                'payments.created_at as occurred_at',
                DB::raw("'payment' as event_type"),
                DB::raw("'registered' as action"),
                'payments.sale_id as reference',
                'sales.customer_name as context',
                'payments.reference as subject',
                DB::raw('NULL as notes'),
                'payments.amount',
                'payment_methods.name as method',
                'cashiers.name as actor',
                DB::raw('NULL as previous_method'),
                DB::raw('NULL as previous_amount'),
            ]);

        $paymentEdits = PaymentEdit::query()
            ->join('sales', 'sales.id', '=', 'payment_edits.sale_id')
            ->leftJoin('payment_methods as previous_methods', 'previous_methods.id', '=', 'payment_edits.previous_payment_method_id')
            ->leftJoin('payment_methods as new_methods', 'new_methods.id', '=', 'payment_edits.new_payment_method_id')
            ->leftJoin('users as editors', 'editors.id', '=', 'payment_edits.edited_by')
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('payment_edits.created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('payment_edits.created_at', '<=', $to))
            ->select([
                'payment_edits.created_at as occurred_at',
                DB::raw("'payment_edit' as event_type"),
                DB::raw("'corrected' as action"),
                'payment_edits.sale_id as reference',
                'sales.customer_name as context',
                'payment_edits.new_reference as subject',
                'payment_edits.reason as notes',
                'payment_edits.new_amount as amount',
                'new_methods.name as method',
                'editors.name as actor',
                'previous_methods.name as previous_method',
                'payment_edits.previous_amount',
            ]);

        $tips = TipAdjustment::query()
            ->leftJoin('payment_methods', 'payment_methods.id', '=', 'tip_adjustments.payment_method_id')
            ->leftJoin('users as adjusters', 'adjusters.id', '=', 'tip_adjustments.adjusted_by')
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('adjusted_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('adjusted_at', '<=', $to))
            ->select([
                'adjusted_at as occurred_at',
                DB::raw("'tip' as event_type"),
                DB::raw("'adjusted' as action"),
                'sale_id as reference',
                DB::raw('NULL as context'),
                'reference as subject',
                'reason as notes',
                'amount',
                'payment_methods.name as method',
                'adjusters.name as actor',
                DB::raw('NULL as previous_method'),
                DB::raw('NULL as previous_amount'),
            ]);

        $eventSource = $corrections->unionAll($payments)->unionAll($paymentEdits);
        if ($tipsEnabled) {
            $eventSource->unionAll($tips);
        }

        $events = DB::query()
            ->fromSub($eventSource, 'audit_events')
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('event_type', $type))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('context', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhere('reference', $search);
                });
            })
            ->orderByDesc('occurred_at')
            ->paginate(30)
            ->withQueryString();

        return view('audits.index', [
            'events' => $events,
            'filters' => $filters,
            'currencySymbol' => Setting::first()?->currency_simbol ?? '',
            'tipsEnabled' => $tipsEnabled,
        ]);
    }
}
