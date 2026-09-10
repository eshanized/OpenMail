<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use Livewire\Component;

class MailTab extends Component
{
    public int $pageSize = 25;

    public string $defaultFolder = 'INBOX';

    public string $replyBehavior = 'reply';

    public function mount(): void
    {
        $user = auth()->user();
        $this->pageSize = (int) $user->setting('page_size', 25);
        $this->defaultFolder = $user->setting('default_folder', 'INBOX');
        $this->replyBehavior = $user->setting('reply_behavior', 'reply');
    }

    public function save(): void
    {
        $userId = auth()->id();
        Setting::setForUser($userId, 'page_size', $this->pageSize, 'mail');
        Setting::setForUser($userId, 'default_folder', $this->defaultFolder, 'mail');
        Setting::setForUser($userId, 'reply_behavior', $this->replyBehavior, 'mail');

        $this->dispatch('toast', message: 'Mail preferences updated', type: 'success');
    }

    public function render()
    {
        return view('livewire.settings.mail-tab');
    }
}
