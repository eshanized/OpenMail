@extends('layouts.mailbox')

@section('sidebar')
    <livewire:mailbox.folder-sidebar wire:key="mailbox-folder-sidebar" />
@endsection

@section('mailbox-content')
    <div x-data="{ selected: [] }" class="mailbox-route-content h-full min-h-0">
        @if(isset($uid))
            <div class="mailbox-three-pane" aria-label="Mailbox workspace">
                <section class="mailbox-three-pane-list" aria-label="Messages in {{ $folderPath }}">
                    <livewire:mailbox.message-list
                        :folderPath="$folderPath ?? 'INBOX'"
                    />
                </section>
                <section class="mailbox-three-pane-reader" aria-label="Selected message">
                    <livewire:mailbox.message-viewer
                        :folderPath="$folderPath"
                        :uid="$uid"
                    />
                </section>
            </div>
        @else
            <livewire:mailbox.message-list
                :folderPath="$folderPath ?? 'INBOX'"
            />
        @endif

        {{-- Composer Modal --}}
        <livewire:mailbox.composer />
    </div>
@endsection