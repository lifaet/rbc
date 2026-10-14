<div class="max-w-5xl mx-auto p-6 lg:p-8">

    @if (! $cycle)
        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-5">
            There is no active survey cycle open right now. Please check back later.
        </div>
    @else
        <!-- Header -->
        <div class="mb-6">
            <h1 class="font-display text-2xl font-bold text-gray-900">{{ $cycle->name }} ESG Survey</h1>
            <p class="text-sm text-gray-500 mt-1">Deadline: {{ $cycle->close_date->format('d M, Y') }}</p>
            <div class="accent-mark"></div>
        </div>

        <!-- Overall progress -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-6 flex items-center gap-5">
            <div class="relative w-16 h-16 shrink-0">
                <svg viewBox="0 0 36 36" class="w-16 h-16 -rotate-90">
                    <circle cx="18" cy="18" r="15.5" fill="none" stroke="#E1E1FC" stroke-width="4"/>
                    <circle cx="18" cy="18" r="15.5" fill="none" stroke="#5B5FEF" stroke-width="4"
                        stroke-dasharray="{{ 2 * 3.1416 * 15.5 }}"
                        stroke-dashoffset="{{ 2 * 3.1416 * 15.5 * (1 - $overallProgress['percent'] / 100) }}"
                        stroke-linecap="round"/>
                </svg>
                <span class="absolute inset-0 flex items-center justify-center text-xs font-semibold text-brand-700">
                    {{ $overallProgress['percent'] }}%
                </span>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-800">{{ $overallProgress['answered'] }} of {{ $overallProgress['total'] }} questions answered</p>
                <p class="text-xs text-gray-500 mt-0.5">Progress saves automatically as you move between sections.</p>
            </div>
        </div>

        @if ($statusMessage)
            <div class="mb-4 p-3 rounded-lg text-sm
                {{ $statusType === 'success' ? 'bg-emerald-50 text-emerald-700' : ($statusType === 'error' ? 'bg-red-50 text-alert' : 'bg-brand-50 text-brand-700') }}">
                {{ $statusMessage }}
            </div>
        @endif

        @if ($submission && $submission->isSubmitted())
            <div class="mb-4 p-3 rounded-lg bg-emerald-50 text-emerald-700 text-sm">
                Submitted on {{ $submission->submitted_at->format('d M, Y H:i') }}. You may still edit and resubmit until the cycle closes.
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

            <!-- Category tabs (sidebar on desktop, horizontal scroll on mobile) -->
            <div class="lg:col-span-1">
                <div class="flex lg:flex-col gap-2 overflow-x-auto lg:overflow-visible pb-2 lg:pb-0">
                    @foreach ($categories as $category)
                        @php $p = $categoryProgress[$category->id]; $complete = $p['total'] > 0 && $p['answered'] === $p['total']; @endphp
                        <button wire:click="goToCategory({{ $category->id }})"
                            class="shrink-0 text-left px-4 py-3 rounded-xl border transition-colors min-w-[180px] lg:min-w-0
                                {{ $activeCategory && $activeCategory->id === $category->id
                                    ? 'bg-brand-700 border-brand-700 text-white'
                                    : 'bg-white border-gray-100 text-gray-700 hover:border-brand-200' }}">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm font-medium">{{ $category->name }}</span>
                                @if ($complete)
                                    <span class="text-xs {{ $activeCategory && $activeCategory->id === $category->id ? 'text-white' : 'text-emerald-600' }}">✓</span>
                                @endif
                            </div>
                            <span class="text-xs {{ $activeCategory && $activeCategory->id === $category->id ? 'text-brand-100' : 'text-gray-400' }}">
                                {{ $p['answered'] }}/{{ $p['total'] }} answered
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Active category form -->
            <div class="lg:col-span-3">
                @if ($activeCategory)
                    <form wire:submit.prevent="submit" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                        <h2 class="font-display text-lg font-semibold text-gray-900 mb-5">{{ $activeCategory->name }}</h2>

                        <div class="space-y-6">
                            @foreach ($activeCategory->questions as $question)
                                <div wire:key="q-input-{{ $question->id }}">
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                        {{ $question->text }}
                                        @if ($question->is_required)
                                            <span class="text-alert">*</span>
                                        @endif
                                    </label>
                                    @if($question->help_text)<p class="text-xs text-gray-400 mb-1">{{ $question->help_text }}</p>@endif

                                    @if ($question->type === 'text')
                                        <textarea wire:model.lazy="answers.{{ $question->id }}" rows="2"
                                            class="w-full rounded-lg border-gray-300 focus:ring-brand-600 focus:border-brand-600"></textarea>

                                    @elseif ($question->type === 'number')
                                        <input type="number" step="any" min="0" wire:model.lazy="answers.{{ $question->id }}"
                                            class="w-full rounded-lg border-gray-300 focus:ring-brand-600 focus:border-brand-600" />

                                    @elseif ($question->type === 'date')
                                        <input type="date" wire:model.lazy="answers.{{ $question->id }}"
                                            class="w-full rounded-lg border-gray-300 focus:ring-brand-600 focus:border-brand-600" />

                                    @elseif ($question->type === 'single_choice')
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($question->options ?? [] as $option)
                                                <label class="px-3.5 py-1.5 rounded-full border text-sm cursor-pointer transition-colors
                                                    {{ ($answers[$question->id] ?? null) === $option ? 'bg-brand-700 border-brand-700 text-white' : 'border-gray-200 text-gray-600 hover:border-brand-300' }}">
                                                    <input type="radio" wire:model.live="answers.{{ $question->id }}" value="{{ $option }}" class="hidden" />
                                                    {{ $option }}
                                                </label>
                                    @if($question->help_text)<p class="text-xs text-gray-400 mb-1">{{ $question->help_text }}</p>@endif
                                            @endforeach
                                        </div>

                                    @elseif ($question->type === 'multi_choice')
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($question->options ?? [] as $option)
                                                <label class="px-3.5 py-1.5 rounded-full border text-sm cursor-pointer transition-colors
                                                    {{ is_array($answers[$question->id] ?? null) && in_array($option, $answers[$question->id]) ? 'bg-brand-700 border-brand-700 text-white' : 'border-gray-200 text-gray-600 hover:border-brand-300' }}">
                                                    <input type="checkbox" wire:model.live="answers.{{ $question->id }}" value="{{ $option }}" class="hidden" />
                                                    {{ $option }}
                                                </label>
                                    @if($question->help_text)<p class="text-xs text-gray-400 mb-1">{{ $question->help_text }}</p>@endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        </div>
                        <div class="flex items-center justify-between mt-8 pt-5 border-t border-gray-100">
                            <button type="button" wire:click="previousCategory"
                                class="px-4 py-2 text-sm rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">
                                ← Previous
                            </button>
                            <div class="flex gap-2">
                                <button type="button" wire:click="saveDraft"
                                    class="px-4 py-2 text-sm rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50">
                                    Save Draft
                                </button>
                                <button type="button" wire:click="nextCategory"
                                    class="px-4 py-2 text-sm rounded-lg bg-brand-100 text-brand-700 hover:bg-brand-200">
                                    Next →
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 text-sm rounded-lg bg-brand-700 text-white hover:bg-brand-800">
                                    Submit Survey
                                </button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>
