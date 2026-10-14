<div class="max-w-5xl mx-auto p-6">
    <h1 class="font-display text-2xl font-bold text-gray-900">Manage Survey Cycles</h1>
    <div class="flax-mark"></div>

    <form wire:submit.prevent="save" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-8 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Cycle Name</label>
            <input type="text" wire:model="name" placeholder="e.g. 2026 H1"
                class="w-full rounded-lg border-gray-300 focus:ring-brand-600 focus:border-brand-600" />
            @error('name') <span class="text-alert text-xs">{{ $message }}</span> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Open Date</label>
            <input type="date" wire:model="open_date"
                class="w-full rounded-lg border-gray-300 focus:ring-brand-600 focus:border-brand-600" />
            @error('open_date') <span class="text-alert text-xs">{{ $message }}</span> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Close Date</label>
            <input type="date" wire:model="close_date"
                class="w-full rounded-lg border-gray-300 focus:ring-brand-600 focus:border-brand-600" />
            @error('close_date') <span class="text-alert text-xs">{{ $message }}</span> @enderror
        </div>
        <div class="md:col-span-4">
            <button type="submit" class="px-5 py-2 rounded-lg bg-brand-700 text-white shadow-sm hover:bg-brand-800">
                {{ $editingId ? 'Update Cycle' : 'Create Cycle' }}
            </button>
        </div>
    </form>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Window</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Submissions</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cycles as $cycle)
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $cycle->name }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $cycle->open_date->format('d M Y') }} – {{ $cycle->close_date->format('d M Y') }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs
                                {{ $cycle->status === 'open' ? 'bg-brand-50 text-brand-700' : ($cycle->status === 'closed' ? 'bg-gray-200 text-gray-600' : 'bg-yellow-100 text-yellow-700') }}">
                                {{ ucfirst($cycle->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $cycle->submissions_count }}</td>
                        <td class="px-4 py-3 space-x-2">
                            <button wire:click="edit({{ $cycle->id }})" class="text-blue-600 hover:underline text-xs">Edit</button>
                            <button wire:click="toggleStatus({{ $cycle->id }})" class="text-orange-600 hover:underline text-xs">
                                {{ $cycle->status === 'open' ? 'Close' : 'Open' }}
                            </button>
                            <a href="{{ route('admin.questions', $cycle->id) }}" class="text-brand-700 hover:underline text-xs">Questions</a>
                            <button wire:click="cloneFromPrevious({{ $cycle->id }})" class="text-gray-600 hover:underline text-xs">Clone</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
