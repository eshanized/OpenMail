<?php

declare(strict_types=1);

namespace App\Livewire\Mailbox;

use App\Models\FollowUpReminder;
use App\Services\FollowUpReminderService;
use Livewire\Component;
use Livewire\WithPagination;

class FollowUpReminders extends Component
{
    use WithPagination;

    public function completeReminder(int $reminderId): void
    {
        $completed = app(FollowUpReminderService::class)->complete(
            (int) auth()->id(),
            $reminderId
        );

        $this->dispatch(
            'toast',
            $completed ? 'Follow-up marked complete.' : 'This reminder is no longer available.',
            $completed ? 'success' : 'warning'
        );

        $this->resetPage();
    }

    public function dismissReminder(int $reminderId): void
    {
        $dismissed = app(FollowUpReminderService::class)->dismiss(
            (int) auth()->id(),
            $reminderId
        );

        $this->dispatch(
            'toast',
            $dismissed ? 'Follow-up reminder dismissed.' : 'This reminder is no longer available.',
            $dismissed ? 'success' : 'warning'
        );

        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.mailbox.follow-up-reminders', [
            'reminders' => FollowUpReminder::query()
                ->where('user_id', auth()->id())
                ->where('status', FollowUpReminder::STATUS_OPEN)
                ->orderBy('remind_at')
                ->orderBy('id')
                ->paginate(20),
        ]);
    }
}
