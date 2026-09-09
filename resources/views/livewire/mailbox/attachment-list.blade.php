@props(['attachments', 'folderPath', 'uid'])

<div class="mt-6 pt-5 border-t border-border px-6 pb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-ink flex items-center gap-2">
            <span class="w-6 h-6 rounded-md bg-surface-sunken flex items-center justify-center text-ink-tertiary">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/>
                </svg>
            </span>
            <span>Attachments</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-surface-sunken border border-border-subtle text-ink-secondary">
                {{ count($attachments) }}
            </span>
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
                    $theme = 'indigo';
                    $icon = 'document';

                    if (str_starts_with($mimeType, 'image/') || in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'])) {
                        $theme = 'blue';
                        $icon = 'image';
                    } elseif (str_contains($mimeType, 'pdf') || $ext === 'pdf') {
                        $theme = 'rose';
                        $icon = 'document-text';
                    } elseif (str_contains($mimeType, 'excel') || str_contains($mimeType, 'spreadsheet') || in_array($ext, ['xls', 'xlsx', 'csv'])) {
                        $theme = 'emerald';
                        $icon = 'table-cells';
                    } elseif (str_contains($mimeType, 'presentation') || str_contains($mimeType, 'powerpoint') || in_array($ext, ['ppt', 'pptx'])) {
                        $theme = 'amber';
                        $icon = 'presentation-chart-bar';
                    } elseif (str_contains($mimeType, 'zip') || str_contains($mimeType, 'compressed') || in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'])) {
                        $theme = 'purple';
                        $icon = 'archive-box';
                    }
                @endphp

                <div class="attachment-card group">
                    {{-- File icon pill --}}
                    <div class="attachment-icon-pill mr-3
                        @if($theme === 'rose') bg-rose-500/10 text-rose-500 border border-rose-500/20
                        @elseif($theme === 'blue') bg-blue-500/10 text-blue-500 border border-blue-500/20
                        @elseif($theme === 'emerald') bg-emerald-500/10 text-emerald-500 border border-emerald-500/20
                        @elseif($theme === 'amber') bg-amber-500/10 text-amber-500 border border-amber-500/20
                        @elseif($theme === 'purple') bg-purple-500/10 text-purple-500 border border-purple-500/20
                        @else bg-indigo-500/10 text-indigo-500 border border-indigo-500/20
                        @endif">
                        @if($icon === 'image')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                            </svg>
                        @elseif($icon === 'document-text')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                            </svg>
                        @elseif($icon === 'table-cells')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                            </svg>
                        @elseif($icon === 'presentation-chart-bar')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.25 2.25L15 7.5"/>
                            </svg>
                        @elseif($icon === 'archive-box')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                            </svg>
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                            </svg>
                        @endif
                    </div>

                    {{-- File details --}}
                    <div class="flex-1 min-w-0 pr-2">
                        <p class="text-xs font-semibold text-ink truncate leading-tight" title="{{ $attachment['name'] }}">
                            {{ $attachment['name'] }}
                        </p>
                        <p class="text-[11px] text-ink-tertiary mt-1 font-mono">
                            {{ $attachment['size'] ? number_format($attachment['size'] / 1024, 1) . ' KB' : 'Unknown size' }}
                        </p>
                    </div>

                    {{-- Download Button --}}
                    <button
                        wire:click="download({{ $attachment['index'] }})"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-primary hover:bg-primary-hover rounded-lg shadow-xs transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed flex-shrink-0 cursor-pointer"
                        title="Download {{ $attachment['name'] }}"
                    >
                        <span wire:loading.remove wire:target="download({{ $attachment['index'] }})" class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                            </svg>
                            <span>Download</span>
                        </span>
                        <span wire:loading wire:target="download({{ $attachment['index'] }})" class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 spin-animation" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span>...</span>
                        </span>
                    </button>
                </div>
            @endforeach
        </div>
    @endif
</div>