@props(['attachments', 'folderPath', 'uid'])

<div class="mt-6 pt-4 border-t border-gray-200">
    <h3 class="text-lg font-medium text-gray-900 mb-3 flex items-center">
        <svg class="w-5 h-5 text-gray-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path d="M15.828 7.828a2 2 0 112.828 2.828l-7.071 7.071a2 2 0 01-2.828 0l-7.071-7.071a2 2 0 112.828-2.828L10 12.172l5.828-5.828z"></path>
        </svg>
        Attachments ({{ count($attachments) }})
    </h3>

    @if(empty($attachments))
        <p class="text-gray-500 text-sm">No attachments</p>
    @else
        <div class="space-y-2">
            @foreach($attachments as $attachment)
                <div class="flex items-center p-3 bg-gray-50 rounded-lg border border-gray-100">
                    {{-- File icon based on MIME type --}}
                    <div class="w-10 h-10 flex-shrink-0 flex items-center justify-center bg-white rounded border border-gray-200">
                        @php
                            $mimeType = $attachment['contentType'] ?? 'application/octet-stream';
                            $icon = 'document';
                            if (str_starts_with($mimeType, 'image/')) {
                                $icon = 'image';
                            } elseif (str_contains($mimeType, 'pdf')) {
                                $icon = 'document-text';
                            } elseif (str_contains($mimeType, 'word') || str_contains($mimeType, 'document')) {
                                $icon = 'document-text';
                            } elseif (str_contains($mimeType, 'excel') || str_contains($mimeType, 'spreadsheet')) {
                                $icon = 'table-cells';
                            } elseif (str_contains($mimeType, 'presentation') || str_contains($mimeType, 'powerpoint')) {
                                $icon = 'presentation-chart-bar';
                            } elseif (str_contains($mimeType, 'zip') || str_contains($mimeType, 'compressed') || str_contains($mimeType, 'rar') || str_contains($mimeType, '7z')) {
                                $icon = 'archive-box';
                            }
                        @endphp

                        @if($icon === 'image')
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        @elseif($icon === 'document-text')
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        @elseif($icon === 'table-cells')
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        @elseif($icon === 'presentation-chart-bar')
                            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        @elseif($icon === 'archive-box')
                            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                            </svg>
                        @else
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828L18 9.828l6.586 6.586a2 2 0 002.828-2.828L10.828 2.172a2 2 0 00-2.828 0L2.172 9.828a2 2 0 000 2.828l6.586 6.586a2 2 0 102.828-2.828z"></path>
                            </svg>
                        @endif
                    </div>

                    {{-- File info --}}
                    <div class="ml-3 flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $attachment['name'] }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $attachment['size'] ? number_format($attachment['size'] / 1024, 1) . ' KB' : 'Unknown size' }}
                            @if($attachment['contentType'])
                                <span class="text-gray-400 mx-1">|</span>
                                {{ $attachment['contentType'] }}
                            @endif
                        </p>
                    </div>

                    {{-- Download button --}}
                    <button
                        wire:click="download({{ $attachment['index'] }})"
                        wire:loading.attr="disabled"
                        class="ml-3 flex-shrink-0 px-3 py-1.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span wire:loading.remove>Download</span>
                        <span wire:loading>
                            <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </span>
                    </button>
                </div>
            @endforeach
        </div>
    @endif
</div>