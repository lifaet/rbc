<div class="max-w-6xl mx-auto p-6 lg:p-8">
    <h1 class="font-display text-2xl font-bold text-gray-900">Reporting Dashboard</h1>
    <div class="accent-mark"></div>

    <!-- Cycle picker: clickable pills, not a dropdown -->
    <div class="flex items-center gap-2 overflow-x-auto mt-6 mb-5 pb-1">
        @foreach ($cycles as $cycle)
            <button wire:click="selectCycle({{ $cycle->id }})"
                class="shrink-0 px-4 py-2 rounded-xl text-sm font-medium border transition-colors
                    {{ $selectedCycle && $selectedCycle->id === $cycle->id
                        ? 'bg-brand-700 border-brand-700 text-white'
                        : 'bg-white border-gray-200 text-gray-600 hover:border-brand-300' }}">
                {{ $cycle->name }}
                @if ($cycle->status === 'open')
                    <span class="ml-1.5 text-[10px] uppercase tracking-wide {{ $selectedCycle && $selectedCycle->id === $cycle->id ? 'text-brand-100' : 'text-emerald-600' }}">● open</span>
                @endif
            </button>
        @endforeach
    </div>

    <!-- Overview cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Factories</p>
            <p class="font-display text-3xl font-bold text-gray-900 mt-1">{{ $summary['total_factories'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Submitted</p>
            <p class="font-display text-3xl font-bold text-gray-900 mt-1">{{ $summary['submitted'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Pending</p>
            <p class="font-display text-3xl font-bold text-alert mt-1">{{ $summary['pending'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Completion Rate</p>
            <p class="font-display text-3xl font-bold text-brand-700 mt-1">{{ $summary['percent'] }}%</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="inline-flex p-1 bg-gray-100 rounded-xl mb-6">
        @foreach (['overview' => 'Overview', 'factory' => 'Factory-wise', 'category' => 'Category-wise', 'question' => 'Question-wise'] as $mode => $label)
            <button wire:click="setViewMode('{{ $mode }}')"
                class="px-4 py-1.5 text-sm rounded-lg transition-colors {{ $viewMode === $mode ? 'bg-white text-brand-700 shadow-sm font-medium' : 'text-gray-500 hover:text-gray-700' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- ============================= OVERVIEW ============================= --}}
    @if ($viewMode === 'overview')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Category snapshot grid -->
            <div class="lg:col-span-2">
                <h2 class="text-sm font-semibold text-gray-700 mb-3">Response rate by category</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @forelse ($categoryOverview as $row)
                        <button wire:click="goToCategory({{ $row['category']->id }})"
                            class="text-left bg-white rounded-2xl border border-gray-100 shadow-sm p-4 hover:border-brand-300 transition-colors">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-sm font-medium text-gray-800">{{ $row['category']->name }}</p>
                                <span class="text-xs text-gray-400">{{ $row['question_count'] }} q</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2 mb-1">
                                <div class="h-2 rounded-full {{ $row['response_rate'] >= 70 ? 'bg-emerald-500' : ($row['response_rate'] >= 40 ? 'bg-amber-400' : 'bg-alert') }}"
                                     style="width: {{ $row['response_rate'] }}%"></div>
                            </div>
                            <p class="text-xs text-gray-500">{{ $row['response_rate'] }}% answered</p>
                        </button>
                    @empty
                        <p class="text-sm text-gray-400 col-span-2">No categories in this cycle yet.</p>
                    @endforelse
                </div>
            </div>

            <!-- Factories needing attention -->
            <div>
                <h2 class="text-sm font-semibold text-gray-700 mb-3">Factories pending submission</h2>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm divide-y divide-gray-100 max-h-[420px] overflow-y-auto">
                    @php $pending = $factoryStatusList->where('submitted', false); @endphp
                    @forelse ($pending->take(20) as $row)
                        <div class="px-4 py-3 flex items-center justify-between">
                            <span class="text-sm text-gray-700">{{ $row['factory']->name }}</span>
                            <span class="text-[10px] uppercase tracking-wide text-alert bg-red-50 px-2 py-0.5 rounded-full">Pending</span>
                        </div>
                    @empty
                        <p class="px-4 py-6 text-sm text-gray-400 text-center">All factories have submitted 🎉</p>
                    @endforelse
                    @if ($pending->count() > 20)
                        <p class="px-4 py-2 text-xs text-gray-400">+{{ $pending->count() - 20 }} more</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ============================= FACTORY-WISE ============================= --}}
    @if ($viewMode === 'factory')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div>
                <input type="text" wire:model.live.debounce.300ms="factorySearch" placeholder="Search factories…"
                    class="w-full rounded-lg border-gray-300 text-sm mb-3 focus:ring-brand-600 focus:border-brand-600" />

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm divide-y divide-gray-100 max-h-[520px] overflow-y-auto">
                    @forelse ($factoryStatusList as $row)
                        <button wire:click="selectFactory({{ $row['factory']->id }})"
                            class="w-full text-left px-4 py-3 flex items-center justify-between hover:bg-gray-50 {{ $row['factory']->id === $selectedFactoryId ? 'bg-brand-50' : '' }}">
                            <span class="text-sm text-gray-700">{{ $row['factory']->name }}</span>
                            <span class="text-[10px] uppercase tracking-wide px-2 py-0.5 rounded-full
                                {{ $row['submitted'] ? 'text-emerald-700 bg-emerald-50' : 'text-alert bg-red-50' }}">
                                {{ $row['submitted'] ? 'Submitted' : 'Pending' }}
                            </span>
                        </button>
                    @empty
                        <p class="px-4 py-6 text-sm text-gray-400 text-center">No factories match "{{ $factorySearch }}"</p>
                    @endforelse
                </div>
            </div>

            <div class="lg:col-span-2">
                @if ($factoryReport->isNotEmpty())
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($factoryReport as $categoryName => $answers)
                            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                                <h3 class="font-display font-semibold text-gray-900 mb-3">{{ $categoryName }}</h3>
                                <ul class="divide-y divide-gray-100">
                                    @foreach ($answers as $answer)
                                        <li class="py-2.5">
                                            <p class="text-sm text-gray-700">{{ $answer->question->text }}</p>
                                            <p class="text-sm text-brand-700 font-medium mt-0.5">{{ $answer->value }}</p>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white rounded-2xl border border-gray-100 p-10 text-center text-sm text-gray-400">
                        Select a factory from the list to view its submitted data.
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ============================= CATEGORY-WISE ============================= --}}
    @if ($viewMode === 'category')
        <div class="flex flex-wrap gap-2 mb-5">
            @foreach ($categories as $category)
                <button wire:click="selectCategory({{ $category->id }})"
                    class="px-4 py-2 rounded-full text-sm border transition-colors
                        {{ $selectedCategoryId === $category->id ? 'bg-brand-700 border-brand-700 text-white' : 'bg-white border-gray-200 text-gray-600 hover:border-brand-300' }}">
                    {{ $category->name }}
                </button>
            @endforeach
        </div>

        @if (! empty($categoryReport))
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($categoryReport as $row)
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                        <p class="text-sm font-medium text-gray-800 mb-3">{{ $row['question']->text }}</p>
                        @if ($row['chart'])
                            <div wire:key="cat-chart-{{ $row['question']->id }}-{{ $selectedCycleId }}" class="h-48">
                                <canvas
                                    x-data
                                    x-init="new Chart($el, {
                                        type: @js($row['chart']['chartType']),
                                        data: { labels: @js($row['chart']['labels']), datasets: [{ data: @js($row['chart']['values']), backgroundColor: ['#5B5FEF','#8B8EF2','#C4C4F9','#F0483E','#FBBF24','#10B981'], borderRadius: 6 }] },
                                        options: { responsive: true, maintainAspectRatio: false,
                                            plugins: { legend: { display: @js($row['chart']['chartType'] === 'doughnut'), position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
                                            scales: @js($row['chart']['chartType'] === 'bar') ? { y: { beginAtZero: true } } : {} }
                                    })"
                                ></canvas>
                            </div>
                        @endif
                        <p class="text-xs text-gray-400 mt-2">{{ $row['stats']['total_responses'] }} responses</p>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-white rounded-2xl border border-gray-100 p-10 text-center text-sm text-gray-400">
                Pick a category above — every question in it will show here at once.
            </div>
        @endif
    @endif

    {{-- ============================= QUESTION-WISE ============================= --}}
    @if ($viewMode === 'question')
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach ($categories as $category)
                <button wire:click="selectCategory({{ $category->id }})"
                    class="px-4 py-2 rounded-full text-sm border transition-colors
                        {{ $selectedCategoryId === $category->id ? 'bg-brand-700 border-brand-700 text-white' : 'bg-white border-gray-200 text-gray-600 hover:border-brand-300' }}">
                    {{ $category->name }}
                </button>
            @endforeach
        </div>

        @if ($selectedCategoryId)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
                @foreach ($questionsForSelectedCategory as $question)
                    <button wire:click="selectQuestion({{ $question->id }})"
                        class="text-left px-4 py-3 rounded-xl border text-sm transition-colors
                            {{ $selectedQuestionId === $question->id ? 'bg-brand-700 border-brand-700 text-white' : 'bg-white border-gray-100 text-gray-700 hover:border-brand-300' }}">
                        {{ Str::limit($question->text, 70) }}
                    </button>
                @endforeach
            </div>
        @endif

        @if ($selectedQuestionReport)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <p class="text-sm font-medium text-gray-800 mb-4">{{ $selectedQuestionReport['question']->text }}</p>

                @if ($selectedQuestionReport['chart'])
                    <div wire:key="q-chart-{{ $selectedQuestionReport['question']->id }}-{{ $selectedCycleId }}" class="h-72 max-w-lg mx-auto">
                        <canvas
                            x-data
                            x-init="new Chart($el, {
                                type: @js($selectedQuestionReport['chart']['chartType']),
                                data: { labels: @js($selectedQuestionReport['chart']['labels']), datasets: [{ data: @js($selectedQuestionReport['chart']['values']), backgroundColor: ['#5B5FEF','#8B8EF2','#C4C4F9','#F0483E','#FBBF24','#10B981'], borderRadius: 6 }] },
                                options: { responsive: true, maintainAspectRatio: false,
                                    plugins: { legend: { display: @js($selectedQuestionReport['chart']['chartType'] === 'doughnut'), position: 'bottom' } },
                                    scales: @js($selectedQuestionReport['chart']['chartType'] === 'bar') ? { y: { beginAtZero: true } } : {} }
                            })"
                        ></canvas>
                    </div>
                @else
                    <p class="text-sm text-gray-400 text-center py-8">
                        Free-text question — {{ $selectedQuestionReport['stats']['total_responses'] }} written responses recorded (not chartable).
                    </p>
                @endif
                <p class="text-xs text-gray-400 mt-4 text-center">{{ $selectedQuestionReport['stats']['total_responses'] }} total responses</p>
            </div>
        @elseif (! $selectedCategoryId)
            <div class="bg-white rounded-2xl border border-gray-100 p-10 text-center text-sm text-gray-400">
                Pick a category, then a question, to see its response chart.
            </div>
        @endif
    @endif
</div>
