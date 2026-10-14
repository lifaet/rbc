<div class="max-w-5xl mx-auto p-6 lg:p-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold text-gray-900">Manage Factories</h1>
            <div class="accent-mark"></div>
        </div>
        <button wire:click="openCreateForm" class="px-4 py-2 rounded-lg bg-brand-700 text-white text-sm hover:bg-brand-800">
            + Add Factory
        </button>
    </div>

    <!-- Add / Edit form -->
    @if ($showForm)
        <div class="bg-white rounded-2xl border border-brand-200 ring-1 ring-brand-100 shadow-sm p-5 my-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">
                {{ $editingId ? 'Edit Factory' : 'Add New Factory' }}
            </h2>
            <form wire:submit.prevent="save" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Factory Name</label>
                    <input type="text" wire:model="name" class="w-full rounded-lg border-gray-300" />
                    @error('name') <span class="text-alert text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Region</label>
                    <input type="text" wire:model="region" placeholder="e.g. Dhaka" class="w-full rounded-lg border-gray-300" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Contact Email</label>
                    <input type="email" wire:model="contactEmail" class="w-full rounded-lg border-gray-300" />
                    @error('contactEmail') <span class="text-alert text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                    <select wire:model="status" class="w-full rounded-lg border-gray-300">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="md:col-span-2 border-t border-gray-100 pt-4 mt-1">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-3">
                        {{ $editingId ? 'Reset login (optional)' : 'Create login account (optional)' }}
                    </p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Login Email</label>
                    <input type="email" wire:model="loginEmail" class="w-full rounded-lg border-gray-300" />
                    @error('loginEmail') <span class="text-alert text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Password</label>
                    <input type="password" wire:model="loginPassword" placeholder="Leave blank to keep unchanged" class="w-full rounded-lg border-gray-300" />
                    @error('loginPassword') <span class="text-alert text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-brand-700 text-white text-sm hover:bg-brand-800">
                        {{ $editingId ? 'Save Changes' : 'Create Factory' }}
                    </button>
                    <button type="button" wire:click="cancel" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-600 text-sm hover:bg-gray-50">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    @if(session('success'))<div class="p-3 rounded bg-green-50 text-green-700 text-sm mt-4">{{ session('success') }}</div>@endif
    <form wire:submit="importCsv" class="bg-white rounded-xl border p-4 mt-4 flex flex-wrap gap-3 items-end"><div><label class="block text-xs font-medium text-gray-500 mb-1">Bulk import factories (CSV: name,region,district,contact_email,contact_person,contact_phone,login_email,login_password)</label><input type="file" wire:model="csvFile" accept=".csv,.txt" class="text-sm">@error('csvFile')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror</div><button class="px-3 py-2 rounded-lg bg-brand-700 text-white text-sm">Import CSV</button></form>
    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search factories…"
        class="w-full md:w-80 rounded-lg border-gray-300 text-sm my-6 focus:ring-brand-600 focus:border-brand-600" />

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Region</th>
                    <th class="px-4 py-3">Login</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($factories as $factory)
                    <tr class="border-t border-gray-100" wire:key="factory-{{ $factory->id }}">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $factory->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $factory->region ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $factory->users->first()->email ?? '— no login —' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs {{ $factory->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ ucfirst($factory->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 space-x-2">
                            <button wire:click="openEditForm({{ $factory->id }})" class="text-brand-700 hover:underline text-xs">Edit</button>
                            <button wire:click="toggleStatus({{ $factory->id }})" class="text-gray-500 hover:underline text-xs">
                                {{ $factory->status === 'active' ? 'Deactivate' : 'Activate' }}
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $factories->links() }}</div>
</div>
