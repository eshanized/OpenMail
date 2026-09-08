<div class="space-y-6">
    <section>
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Mail Preferences</h3>
        <div class="space-y-4">
            <div>
                <label for="page_size" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Messages per page</label>
                <select id="page_size" wire:model="pageSize"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <div>
                <label for="default_folder" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default folder</label>
                <select id="default_folder" wire:model="defaultFolder"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="INBOX">Inbox</option>
                    <option value="Sent">Sent</option>
                    <option value="Drafts">Drafts</option>
                </select>
            </div>
            <div>
                <label for="reply_behavior" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reply behavior</label>
                <select id="reply_behavior" wire:model="replyBehavior"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="reply">Reply to sender</option>
                    <option value="reply_all">Reply to all</option>
                </select>
            </div>
            <button wire:click="save" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Save Preferences
            </button>
        </div>
    </section>
</div>
