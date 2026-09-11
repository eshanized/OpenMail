@props(['attachments', 'folderPath', 'uid'])

<div class="mt-4 pt-4 border-t border-border px-5 sm:px-6 pb-6">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-xs font-semibold text-ink flex items-center gap-1.5 uppercase tracking-wider">
            <span>Attachments</span>
            <span class="text-ink-tertiary">({{ count($attachments) }})</span>
        </h3>
    </div>

    @if(empty($attachments))
        <p class="text-ink-tertiary text-xs">No attachments in this message.</p>
    @else
        <div class="attachment-grid">
            @foreach($attachments as $attachment)
                @php
                    $mimeType = $attachment['contentType'] ?? 'application/octet-stream';
                    $ext = strtolower(pathinfo($attachment['name'] ?? '', PATHINFO_EXTENSION));
                    $icon = 'document';

                    if (str_starts_with($mimeType, 'image/') || in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'])) {
                        $icon = 'image';
                    } elseif (str_contains($mimeType, 'pdf') || $ext === 'pdf') {
                        $icon = 'document-text';
                    } elseif (str_contains($mimeType, 'excel') || str_contains($mimeType, 'spreadsheet') || in_array($ext, ['xls', 'xlsx', 'csv'])) {
                        $icon = 'table-cells';
                    } elseif (str_contains($mimeType, 'presentation') || str_contains($mimeType, 'powerpoint') || in_array($ext, ['ppt', 'pptx'])) {
                        $icon = 'presentation-chart-bar';
                    } elseif (str_contains($mimeType, 'zip') || str_contains($mimeType, 'compressed') || in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'])) {
                        $icon = 'archive-box';
                    }
                @endphp

                <div class="attachment-card group flex items-center justify-between p-2.5 bg-surface-sunken border border-border rounded">
                    <div class="flex items-center gap-2.5 min-w-0 pr-2">
                        {{-- File icon --}}
                        <div class="w-8 h-8 rounded bg-surface-raised border border-border flex items-center justify-center text-ink-secondary shrink-0">
                            @if($icon === 'image')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                                </svg>
                            @elseif($icon === 'document-text')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                </svg>
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                </svg>
                            @endif
                        </div>

                        {{-- Details --}}
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-ink truncate" title="{{ $attachment['name'] }}">
                                {{ $attachment['name'] }}
                            </p>
                            <p class="text-[10px] text-ink-tertiary font-mono">
                                {{ $attachment['size'] ? number_format($attachment['size'] / 1024, 1) . ' KB' : 'Unknown' }}
                            </p>
                        </div>
                    </div>

                    {{-- Download Button --}}
                    <button
                        wire:click="download({{ $attachment['index'] }})"
                        wire:loading.attr="disabled"
                        class="px-2 py-1 text-xs text-ink-secondary hover:text-ink hover:bg-hover border border-border rounded transition-colors disabled:opacity-50 cursor-pointer shrink-0"
                        title="Download {{ $attachment['name'] }}"
                    >
                        <span wire:loading.remove wire:target="download({{ $attachment['index'] }})" class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                            </svg>
                            <span>Download</span>
                        </span>
                        <span wire:loading wire:target="download({{ $attachment['index'] }})">…</span>
                    </button>
                </div>
            @endforeach
        </div>
    @endif
</div>