<?php

declare(strict_types=1);

namespace App\Livewire\Mailbox;

use App\Models\SnoozedMessage;
use App\Services\SnoozedMessageService;
use Livewire\Component;
use Livewire\WithPagination;

class SnoozedMessages extends Component
{
    use WithPagination;

    public function returnNow(int $snoozedMessageId): void
    {
        $returned = app(SnoozedMessageService::class)->returnNow(
            (int) auth()->id(),
            $snoozedMessageId
        );

        $this->dispatch(
            'toast',
            $returned ? 'Message returned to its mailbox.' : 'This snoozed message is no longer available.',
            $returned ? 'success' : 'warning'
        );

        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.mailbox.snoozed-messages', [
            'messages' => SnoozedMessage::query()
                ->where('user_id', auth()->id())
                ->where('status', SnoozedMessage::STATUS_SNOOZED)
                ->where('wake_at', '>', now())
                ->orderBy('wake_at')
                ->paginate(20),
        ]);
    }
}
