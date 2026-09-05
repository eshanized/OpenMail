{{-- Collapsible Quote Block Component for Reply/Forward --}}
{{-- Props: $attribution (string), $quotedHtml (string), $uid (string) --}}

<blockquote
    class="quote-block border-l-4 border-blue-200 pl-4 ml-4 my-2"
    x-data="{ open: false }"
    :id="'quote-' + uid"
>
    <div class="quote-header text-xs text-gray-500 mb-1 flex items-center gap-2">
        <span>{{ $attribution }}</span>
        <button
            type="button"
            @click="open = !open"
            class="text-blue-600 hover:underline text-sm"
            :aria-expanded="open"
            :aria-controls="'quote-content-' + uid"
        >
            <span x-show="!open">Show quoted text</span>
            <span x-show="open">Hide quoted text</span>
        </button>
    </div>
    <div
        :id="'quote-content-' + uid"
        class="quote-content"
        x-show="open"
        x-transition
    >
        {!! $quotedHtml !!}
    </div>
</blockquote>