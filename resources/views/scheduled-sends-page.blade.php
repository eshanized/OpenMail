@extends('layouts.mailbox')

@section('sidebar')
    <livewire:mailbox.folder-sidebar wire:key="scheduled-folder-sidebar" />
@endsection

@section('mailbox-content')
    <div class="mx-auto w-full max-w-5xl space-y-4">
        <div class="flex flex-col gap-2 border-b border-border pb-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route('mailbox') }}" wire:navigate class="mb-1 inline-flex items-center gap-1.5 text-xs text-ink-tertiary hover:text-ink">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    Back to mailbox
                </a>
                <h1 class="text-xl font-bold tracking-tight text-ink">Scheduled messages</h1>
                <p class="mt-1 text-xs text-ink-tertiary">Review, cancel, or return future messages to Drafts.</p>
            </div>
        </div>

        <livewire:mailbox.scheduled-sends />
        <livewire:mailbox.composer />
    </div>
@endsection
