<?php

declare(strict_types=1);

namespace App\Livewire\Mailbox;

use App\Models\PendingSend;
use Livewire\Component;
use Livewire\WithPagination;

class DeliveryActivity extends Component
{
    use WithPagination;

    public string $statusFilter = 'all';

    private const STATUSES = [
        PendingSend::STATUS_PENDING,
        PendingSend::STATUS_CLAIMED,
        PendingSend::STATUS_PROCESSING,
        PendingSend::STATUS_SENT,
        PendingSend::STATUS_RETRY_WAITING,
        PendingSend::STATUS_CANCELLED,
        PendingSend::STATUS_CANCELLING,
        PendingSend::STATUS_FAILED,
        PendingSend::STATUS_UNCERTAIN,
    ];

    public function updatedStatusFilter(): void
    {
        if (! in_array($this->statusFilter, ['all', ...self::STATUSES], true)) {
            $this->statusFilter = 'all';
        }

        $this->resetPage();
    }

    public function render()
    {
        $userId = (int) auth()->id();
        $filter = in_array($this->statusFilter, ['all', ...self::STATUSES], true)
            ? $this->statusFilter
            : 'all';

        $query = PendingSend::query()->where('user_id', $userId);

        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        $counts = PendingSend::query()
            ->where('user_id', $userId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(static fn ($count): int => (int) $count)
            ->all();

        return view('livewire.mailbox.delivery-activity', [
            'sends' => $query->orderByDesc('created_at')->orderByDesc('id')->paginate(20),
            'counts' => $counts,
            'statusFilterValue' => $filter,
            'statusOptions' => [
                'all' => 'All statuses',
                PendingSend::STATUS_PENDING => 'Queued',
                PendingSend::STATUS_CLAIMED => 'Claimed',
                PendingSend::STATUS_PROCESSING => 'Processing',
                PendingSend::STATUS_SENT => 'Sent',
                PendingSend::STATUS_RETRY_WAITING => 'Retry waiting',
                PendingSend::STATUS_CANCELLED => 'Cancelled',
                PendingSend::STATUS_CANCELLING => 'Cancellation in progress',
                PendingSend::STATUS_FAILED => 'Failed',
                PendingSend::STATUS_UNCERTAIN => 'Uncertain delivery',
            ],
        ]);
    }
}
