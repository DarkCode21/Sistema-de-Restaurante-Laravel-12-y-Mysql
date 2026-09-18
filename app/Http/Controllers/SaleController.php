<?php

namespace App\Http\Controllers;

use App\Exports\SalesExport;
use App\Models\Sale;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Maatwebsite\Excel\Facades\Excel;
use chillerlan\QRCode\QRCode;

class SaleController extends Controller
{
    public function index()
    {
        return view('sales.index');
    }


    public function receipt($id)
    {
        $sale = $this->receiptSale((int) $id, !request()->user());
        $receiptCompany = $this->receiptCompany($sale);
        $receiptNumber = sprintf('TV-%s-%06d', $sale->branch->code, $sale->id);
        $verifyUrl = URL::signedRoute('sales.verify', ['sale' => $sale->id]);

        $pdf = Pdf::loadView('sales.receipt', [
            'sale' => $sale,
            'receiptCompany' => $receiptCompany,
            'receiptNumber' => $receiptNumber,
            'qrCode' => (new QRCode())->render($verifyUrl),
            'receiptLogo' => $this->logoDataUri($receiptCompany),
        ])->setPaper([0, 0, 226.77, $this->receiptPaperHeight($sale)], 'portrait');

        return $pdf->stream("ticket_{$id}.pdf");
    }

    public function verify(int $sale)
    {
        $sale = $this->receiptSale($sale, true);
        $receiptCompany = $this->receiptCompany($sale);

        return view('sales.verify', [
            'sale' => $sale,
            'receiptCompany' => $receiptCompany,
            'receiptNumber' => sprintf('TV-%s-%06d', $sale->branch->code, $sale->id),
        ]);
    }

    public function salesPdf(Request $request)
    {
        $config = \App\Models\Setting::first();

        $sales = Sale::with(['order.table', 'order.user'])
            ->when($request->from, fn($q) => $q->whereDate('paid_at', '>=', $request->from))
            ->when($request->to, fn($q) => $q->whereDate('paid_at', '<=', $request->to))
            ->when($request->search, function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $query->where('customer_name', 'like', '%' . $request->search . '%')
                        ->orWhere('id', $request->search)
                        ->orWhereHas('order', fn ($order) => $order
                            ->where('customer_name', 'like', '%' . $request->search . '%')
                            ->orWhereHas('table', fn ($table) => $table->where('name', 'like', '%' . $request->search . '%')));
                });
            })
            ->orderBy('paid_at')
            ->get();

        $data = [
            'config' => $config,
            'sales' => $sales,
            'from' => $request->from,
            'to' => $request->to
        ];

        $pdf = Pdf::loadView('sales.sales-pdf', $data);
        return $pdf->stream('reporte-ventas.pdf');
    }

    public function salesExcel(Request $request)
    {
        $fileName = 'ventas_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new SalesExport($request->all()),
            $fileName
        );
    }

    private function receiptSale(int $id, bool $withoutScopes): Sale
    {
        $query = Sale::with([
            'branch.company',
            'cashier',
            'cashRegister' => fn ($query) => $query->withoutGlobalScopes()->with('branch.company'),
            'order' => fn ($query) => $query->withoutGlobalScopes(),
            'order.table' => fn ($query) => $query->withoutGlobalScopes(),
            'order.table.branch.company',
            'order.user',
            'details.product' => fn ($query) => $query->withoutGlobalScopes(),
            'payments.method' => fn ($query) => $query->withoutGlobalScopes(),
        ]);

        if ($withoutScopes) {
            $query->withoutGlobalScopes();
        }

        $sale = $query->findOrFail($id);

        if (!$sale->branch) {
            $branch = $sale->cashRegister?->branch ?? $sale->order?->table?->branch;
            abort_unless($branch, 404);
            $sale->setRelation('branch', $branch);
        }

        return $sale;
    }

    private function logoDataUri(Setting $setting): ?string
    {
        $disk = Storage::disk('public');

        if (!$setting->logo_path || !$disk->exists($setting->logo_path)) {
            return null;
        }

        $mime = $disk->mimeType($setting->logo_path);
        $decoder = ['image/jpeg' => 'imagecreatefromjpeg', 'image/png' => 'imagecreatefrompng'][$mime] ?? null;

        if (!$decoder || !function_exists($decoder)) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($disk->get($setting->logo_path));
    }

    private function receiptCompany(Sale $sale): Setting
    {
        return Setting::withoutGlobalScopes()->where('company_id', $sale->branch->company_id)->first()
            ?? new Setting([
                'company_name' => $sale->branch->company->name,
                'company_address' => $sale->branch->address,
                'company_phone' => $sale->branch->phone,
                'currency_simbol' => 'S/',
            ]);
    }

    private function receiptPaperHeight(Sale $sale): float
    {
        $detailLines = $sale->details->sum(function ($detail): int {
            $lines = max(1, (int) ceil(mb_strlen($detail->product_name ?: $detail->product?->name ?: '') / 28));
            $lines += !empty($detail->selected_options) ? 1 : 0;
            $lines += $detail->notes ? 1 : 0;

            return $lines;
        });
        $paymentLines = $sale->payments->sum(fn ($payment) => 1 + ($payment->received_amount !== null) + ($payment->returned_amount !== null));
        $summaryLines = 2 + ($sale->tax > 0) + ($sale->manual_discount > 0) + ($sale->tip > 0);

        return max(560, 430 + ($detailLines * 18) + ($paymentLines * 13) + ($summaryLines * 12));
    }
}
