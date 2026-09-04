<?php

namespace App\Livewire;

use App\Models\PrintJob;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class PrintJobComponent extends Component
{
    use WithPagination;

    public function retry(int $id): void
    {
        $this->ensureCanManage();
        $job = PrintJob::findOrFail($id);
        $job->update(['status' => 'queued', 'error' => null]);
        $this->dispatchJob($job, true);
    }

    public function reprint(int $id): void
    {
        $this->ensureCanManage();
        $job = PrintJob::findOrFail($id);
        $copy = PrintJob::create([
            'branch_id' => $job->branch_id,
            'order_id' => $job->order_id,
            'preparation_station_id' => $job->preparation_station_id,
            'printer_name' => $job->printer_name,
            'detail_ids' => $job->detail_ids,
            'correction_ids' => $job->correction_ids,
            'is_correction' => $job->is_correction,
        ]);
        $this->dispatchJob($copy, true);
    }

    #[On('confirm-print-job')]
    public function confirmPrintJob(int $jobId, bool $success, ?string $error = null): void
    {
        $job = PrintJob::find($jobId);
        if (!$job) {
            return;
        }

        $job->update($success
            ? ['status' => 'sent', 'error' => null, 'confirmed_at' => now()]
            : ['status' => 'failed', 'error' => $error ?: 'El agente local no confirmó la impresión.']);
    }

    public function render()
    {
        $jobs = PrintJob::with(['order.table', 'preparationStation'])->latest()->paginate(15);

        return view('livewire.print-job-component', compact('jobs'));
    }

    private function dispatchJob(PrintJob $job, bool $reprint = false): void
    {
        $job->increment('attempts');
        $this->dispatch('print-job', $job->fresh()->payload($reprint));
    }

    private function ensureCanManage(): void
    {
        abort_unless(auth()->user()?->can('empresa.editar'), 403);
    }
}
