<div class="space-y-6">
    <section>
        <h3 class="text-lg font-semibold mb-4 text-ink">Profile</h3>
        <div class="glass-card p-6 space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-ink">Name</label>
                <input type="text" id="name" wire:model="name"
                    class="glass-input mt-1 block w-full rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent sm:text-sm">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-ink">Email</label>
                <input type="email" id="email" wire:model="email"
                    class="glass-input mt-1 block w-full rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent sm:text-sm">
            </div>
            <button wire:click="save" class="group inline-flex items-center gap-2 rounded-lg
                       bg-linear-to-r from-blue-600 to-purple-600
                       px-4 py-2 text-sm font-semibold text-white
                       shadow-glow
                       transition duration-150 ease-out
                       hover:scale-[1.02] hover:shadow-glow-strong
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2
                       focus-visible:ring-blue-600
                       motion-reduce:transform-none motion-reduce:transition-none">
                Save Profile
            </button>
        </div>
    </section>
</div>