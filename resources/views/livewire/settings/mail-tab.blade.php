<div class="space-y-6">
    <section>
        <h3 class="text-lg font-semibold mb-4 text-ink">Mail Preferences</h3>
        <div class="glass-card p-6 space-y-4">
            <div>
                <label for="page_size" class="block text-sm font-medium text-ink">Messages per page</label>
                <select id="page_size" wire:model="pageSize"
                    class="glass-input mt-1 block w-full rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent sm:text-sm">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <div>
                <label for="default_folder" class="block text-sm font-medium text-ink">Default folder</label>
                <select id="default_folder" wire:model="defaultFolder"
                    class="glass-input mt-1 block w-full rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent sm:text-sm">
                    <option value="INBOX">Inbox</option>
                    <option value="Sent">Sent</option>
                    <option value="Drafts">Drafts</option>
                </select>
            </div>
            <div>
                <label for="reply_behavior" class="block text-sm font-medium text-ink">Reply behavior</label>
                <select id="reply_behavior" wire:model="replyBehavior"
                    class="glass-input mt-1 block w-full rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent sm:text-sm">
                    <option value="reply">Reply to sender</option>
                    <option value="reply_all">Reply to all</option>
                </select>
            </div>
            <button wire:click="save" class="group inline-flex items-center gap-2 rounded-lg
                       bg-linear-to-r from-blue-600 to-purple-600
                       px-4 py-2 text-sm font-semibold text-white
                       shadow-glow
                       transition duration-150 ease-out
                       hover:scale-[1.02] hover:shadow-glow-strong
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2
                       focus-visible:ring-primary
                       motion-reduce:transform-none motion-reduce:transition-none">
                Save Preferences
            </button>
        </div>
    </section>
</div>