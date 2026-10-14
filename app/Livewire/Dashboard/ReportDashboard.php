<?php

namespace App\Livewire\Dashboard;

use App\Models\Answer;
use App\Models\Cycle;
use App\Models\Factory;
use App\Models\Question;
use App\Models\Submission;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layout')]
#[Title('Dashboard')]
class ReportDashboard extends Component
{
    // 'overview' | 'factory' | 'category' | 'question'
    public string $viewMode = 'overview';

    public ?int $selectedCycleId = null;
    public ?int $selectedFactoryId = null;
    public ?int $selectedCategoryId = null;
    public ?int $selectedQuestionId = null;

    public string $factorySearch = '';

    public function mount(): void
    {
        $this->selectedCycleId = Cycle::latest('open_date')->value('id');
    }

    public function selectCycle(int $cycleId): void
    {
        $this->selectedCycleId = $cycleId;
        $this->selectedCategoryId = null;
        $this->selectedQuestionId = null;
        $this->selectedFactoryId = null;
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
    }

    public function selectCategory(int $categoryId): void
    {
        $this->selectedCategoryId = $this->selectedCategoryId === $categoryId ? null : $categoryId;
        $this->selectedQuestionId = null;
    }

    public function selectQuestion(int $questionId): void
    {
        $this->selectedQuestionId = $this->selectedQuestionId === $questionId ? null : $questionId;
    }

    public function selectFactory(int $factoryId): void
    {
        $this->selectedFactoryId = $this->selectedFactoryId === $factoryId ? null : $factoryId;
    }

    public function goToCategory(int $categoryId): void
    {
        $this->viewMode = 'category';
        $this->selectedCategoryId = $categoryId;
        $this->selectedQuestionId = null;
    }

    public function getCyclesProperty()
    {
        return Cycle::orderByDesc('open_date')->get();
    }

    public function getSelectedCycleProperty()
    {
        return $this->cycles->firstWhere('id', $this->selectedCycleId);
    }

    public function getFactoriesProperty()
    {
        return Factory::active()->orderBy('name')->get();
    }

    public function getCategoriesProperty()
    {
        if (! $this->selectedCycleId) {
            return collect();
        }

        return Cycle::find($this->selectedCycleId)?->categories()->orderBy('sort_order')->get() ?? collect();
    }

    public function getQuestionsForSelectedCategoryProperty()
    {
        if (! $this->selectedCategoryId) {
            return collect();
        }

        return Question::where('category_id', $this->selectedCategoryId)->orderBy('sort_order')->get();
    }

    public function getSummaryProperty(): array
    {
        if (! $this->selectedCycleId) {
            return ['total_factories' => 0, 'submitted' => 0, 'pending' => 0, 'percent' => 0];
        }

        $totalFactories = Factory::active()->count();
        $submitted = Submission::where('cycle_id', $this->selectedCycleId)->where('status', 'submitted')->count();

        return [
            'total_factories' => $totalFactories,
            'submitted' => $submitted,
            'pending' => $totalFactories - $submitted,
            'percent' => $totalFactories > 0 ? (int) round(($submitted / $totalFactories) * 100) : 0,
        ];
    }

    /**
     * Overview: a snapshot row per category, so the admin sees everything at once
     * instead of having to pick one category from a dropdown first.
     */
    public function getCategoryOverviewProperty()
    {
        if (! $this->selectedCycleId) {
            return collect();
        }

        $submittedCount = max($this->summary['submitted'], 1);

        return $this->categories->map(function ($category) use ($submittedCount) {
            $questionIds = $category->questions->pluck('id');

            $totalPossible = $questionIds->count() * $submittedCount;
            $totalActual = Answer::whereIn('question_id', $questionIds)
                ->whereHas('submission', fn ($q) => $q->where('cycle_id', $this->selectedCycleId)->where('status', 'submitted'))
                ->count();

            return [
                'category' => $category,
                'question_count' => $questionIds->count(),
                'response_rate' => $totalPossible > 0 ? (int) round(($totalActual / $totalPossible) * 100) : 0,
            ];
        });
    }

    /**
     * Factory submission status list, used by both the Overview "needs attention"
     * list and the searchable Factory-wise browser.
     */
    public function getFactoryStatusListProperty()
    {
        if (! $this->selectedCycleId) {
            return collect();
        }

        $submissions = Submission::where('cycle_id', $this->selectedCycleId)
            ->where('status', 'submitted')
            ->pluck('submitted_at', 'factory_id');

        return $this->factories
            ->when($this->factorySearch, fn ($q) => $q->filter(
                fn ($f) => str_contains(strtolower($f->name), strtolower($this->factorySearch))
            ))
            ->map(fn ($factory) => [
                'factory' => $factory,
                'submitted' => $submissions->has($factory->id),
                'submitted_at' => $submissions->get($factory->id),
            ])
            ->values();
    }

    public function getFactoryReportProperty()
    {
        if (! $this->selectedFactoryId || ! $this->selectedCycleId) {
            return collect();
        }

        $submission = Submission::where('factory_id', $this->selectedFactoryId)
            ->where('cycle_id', $this->selectedCycleId)
            ->with('answers.question.category')
            ->first();

        if (! $submission) {
            return collect();
        }

        return $submission->answers->groupBy(fn ($answer) => $answer->question->category->name);
    }

    public function getCategoryReportProperty(): array
    {
        if (! $this->selectedCategoryId) {
            return [];
        }

        return $this->questionsForSelectedCategory->map(function ($question) {
            $stats = $this->statsForQuestion($question);

            return [
                'question' => $question,
                'stats' => $stats,
                'chart' => $this->chartPayloadFor($stats),
            ];
        })->toArray();
    }

    public function getSelectedQuestionReportProperty(): ?array
    {
        if (! $this->selectedQuestionId) {
            return null;
        }

        $question = Question::find($this->selectedQuestionId);
        if (! $question) {
            return null;
        }

        $stats = $this->statsForQuestion($question);

        return ['question' => $question, 'stats' => $stats, 'chart' => $this->chartPayloadFor($stats)];
    }

    protected function statsForQuestion(Question $question): array
    {
        $answers = Answer::where('question_id', $question->id)
            ->whereHas('submission', function ($q) {
                $q->where('cycle_id', $this->selectedCycleId)->where('status', 'submitted');
            })
            ->get();

        if ($question->isChoiceType()) {
            $distribution = [];
            foreach ($answers as $answer) {
                $values = $question->type === 'multi_choice'
                    ? (json_decode($answer->value, true) ?? [])
                    : [$answer->value];

                foreach ($values as $value) {
                    $distribution[$value] = ($distribution[$value] ?? 0) + 1;
                }
            }

            return ['type' => 'distribution', 'data' => $distribution, 'total_responses' => $answers->count()];
        }

        if ($question->type === 'number') {
            $numbers = $answers->pluck('value')->filter(fn ($v) => is_numeric($v))->map(fn ($v) => (float) $v);

            return [
                'type' => 'numeric',
                'average' => $numbers->isNotEmpty() ? round($numbers->avg(), 2) : null,
                'min' => $numbers->min(),
                'max' => $numbers->max(),
                'total_responses' => $answers->count(),
            ];
        }

        return ['type' => 'text', 'total_responses' => $answers->count()];
    }

    protected function chartPayloadFor(array $stats): ?array
    {
        if ($stats['type'] === 'distribution') {
            return [
                'chartType' => 'doughnut',
                'labels' => array_keys($stats['data']),
                'values' => array_values($stats['data']),
            ];
        }

        if ($stats['type'] === 'numeric') {
            return [
                'chartType' => 'bar',
                'labels' => ['Min', 'Average', 'Max'],
                'values' => [$stats['min'] ?? 0, $stats['average'] ?? 0, $stats['max'] ?? 0],
            ];
        }

        return null;
    }

    public function render()
    {
        return view('livewire.dashboard.report-dashboard', [
            'cycles' => $this->cycles,
            'selectedCycle' => $this->selectedCycle,
            'factories' => $this->factories,
            'categories' => $this->categories,
            'questionsForSelectedCategory' => $this->questionsForSelectedCategory,
            'summary' => $this->summary,
            'categoryOverview' => $this->categoryOverview,
            'factoryStatusList' => $this->factoryStatusList,
            'factoryReport' => $this->factoryReport,
            'categoryReport' => $this->categoryReport,
            'selectedQuestionReport' => $this->selectedQuestionReport,
        ]);
    }
}
