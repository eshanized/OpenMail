{{-- Stub: Thread row component - will be fully implemented in Task 2 --}}
<div data-uid="{{ $thread->uid }}" class="px-4 py-3 border-b border-gray-100 last:border-b-0">
    <span>{{ $thread->from_display ?? 'Unknown' }}</span>
    <span>{{ $thread->subject ?? '' }}</span>
</div>
