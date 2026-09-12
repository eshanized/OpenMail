@extends('layouts.mailbox')

@section('sidebar')
    <livewire:mailbox.folder-sidebar wire:key="desktop-folder-sidebar" />
@endsection

@section('mobile-sidebar')
    <livewire:mailbox.folder-sidebar wire:key="mobile-folder-sidebar" />
@endsection

@section('mailbox-content')
    <div x-data="{ selected: [] }">
        @if(isset($uid))
            <livewire:mailbox.message-viewer
                :folderPath="$folderPath"
                :uid="$uid"
            />
        @else
            <livewire:mailbox.message-list
                :folderPath="$folderPath ?? 'INBOX'"
            />
        @endif

        {{-- Composer Modal --}}
        <livewire:mailbox.composer />
    </div>
@endsection