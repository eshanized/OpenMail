<?php

namespace App\Livewire\Mailbox;

use App\Models\PendingSend;
use App\Services\ComposerService;
use Livewire\Component;
use Livewire\WithPagination;

class ScheduledSends extends Component
{
    use WithPagination;

    public function cancelScheduled(int $pendingSendId): void
    {
        $send = PendingSend::query()
            ->where('user_id', auth()->id())
            ->where('is_scheduled', true)
            ->whereKey($pendingSendId)
            ->first();

        if (! $send || ! $send->isCancellable()) {
            $this->dispatch('toast', 'This scheduled message can no longer be cancelled.', 'warning');

            return;
        }

        if (! app(ComposerService::class)->undoSend($send->id, (int) auth()->id())) {
            $this->dispatch('toast', 'The message is already being processed and cannot be cancelled.', 'warning');

            return;
        }

        $this->dispatch('toast', 'Scheduled message cancelled and saved to Drafts.', 'success');
        $this->resetPage();
    }

    public function render()
    {
        $sends = PendingSend::query()
            ->where('user_id', auth()->id())
            ->where('is_scheduled', true)
            ->whereIn('status', [
                PendingSend::STATUS_PENDING,
                PendingSend::STATUS_RETRY_WAITING,
            ])
            ->where('send_at', '>', now())
            ->orderBy('send_at')
            ->paginate(20);

        return view('livewire.mailbox.scheduled-sends', [
            'sends' => $sends,
        ]);
    }
}
