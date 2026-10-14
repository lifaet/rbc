<div class="max-w-2xl mx-auto p-6 lg:p-8">
    <h1 class="font-display text-2xl font-bold text-gray-900">My Profile</h1>
    <div class="accent-mark"></div>

    @if ($statusMessage)
        <div class="mt-5 p-3 rounded-lg text-sm {{ $statusType === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-brand-50 text-brand-700' }}">
            {{ $statusMessage }}
        </div>
    @endif

    <!-- Account info -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mt-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Account Information</h2>
        <form wire:submit.prevent="updateProfile" class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Name</label>
                <input type="text" wire:model="name" class="w-full rounded-lg border-gray-300 focus:ring-brand-600 focus:border-brand-600" />
                @error('name') <span class="text-alert text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                <input type="email" wire:model="email" class="w-full rounded-lg border-gray-300 focus:ring-brand-600 focus:border-brand-600" />
                @error('email') <span class="text-alert text-xs">{{ $message }}</span> @enderror
            </div>

            @if ($isFactoryUser)
                <div class="border-t border-gray-100 pt-4 mt-2">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-3">Factory Information</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Region</label>
                    <input type="text" wire:model="factoryRegion" class="w-full rounded-lg border-gray-300 focus:ring-brand-600 focus:border-brand-600" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Factory Contact Email</label>
                    <input type="email" wire:model="factoryContactEmail" class="w-full rounded-lg border-gray-300 focus:ring-brand-600 focus:border-brand-600" />
                    <p class="text-xs text-gray-400 mt-1">This is where reminder emails are sent for your factory.</p>
                    @error('factoryContactEmail') <span class="text-alert text-xs">{{ $message }}</span> @enderror
                </div>
            @endif

            <button type="submit" class="px-4 py-2 rounded-lg bg-brand-700 text-white text-sm hover:bg-brand-800">
                Save Changes
            </button>
        </form>
    </div>

    <!-- Password -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mt-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Change Password</h2>
        <form wire:submit.prevent="updatePassword" class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Current Password</label>
                <input type="password" wire:model="currentPassword" class="w-full rounded-lg border-gray-300 focus:ring-brand-600 focus:border-brand-600" />
                @error('currentPassword') <span class="text-alert text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">New Password</label>
                <input type="password" wire:model="newPassword" class="w-full rounded-lg border-gray-300 focus:ring-brand-600 focus:border-brand-600" />
                @error('newPassword') <span class="text-alert text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Confirm New Password</label>
                <input type="password" wire:model="newPasswordConfirmation" class="w-full rounded-lg border-gray-300 focus:ring-brand-600 focus:border-brand-600" />
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg bg-brand-700 text-white text-sm hover:bg-brand-800">
                Update Password
            </button>
        </form>
    </div>
</div>
