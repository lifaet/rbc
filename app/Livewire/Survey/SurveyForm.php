<?php

namespace App\Livewire\Survey;

use App\Models\Answer;
use App\Models\Cycle;
use App\Models\Submission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layout')]
#[Title('My Survey')]
class SurveyForm extends Component
{
    public ?Cycle $cycle = null;
    public ?Submission $submission = null;

    /** @var array<int, mixed> question_id => answer value */
    public array $answers = [];

    public ?int $activeCategoryId = null;

    public string $statusMessage = '';
    public string $statusType = 'info'; // info | success | error

    public function mount(): void
    {
        $factory = Auth::user()->factory;

        $this->cycle = Cycle::active()->latest('open_date')->first();

        if (! $this->cycle || ! $factory) {
            return;
        }

        $this->submission = Submission::firstOrCreate(
            ['factory_id' => $factory->id, 'cycle_id' => $this->cycle->id],
            ['status' => 'draft']
        );

        // Initialize every question's answer slot up front. This matters most for
        // multi_choice questions: Livewire's checkbox-array binding requires the
        // underlying property to already exist as an array, or checking/unchecking
        // boxes on a not-yet-answered question behaves inconsistently.
        foreach ($this->cycle->questions as $question) {
            $this->answers[$question->id] = $question->type === 'multi_choice' ? [] : null;
        }

        foreach ($this->submission->answers()->get() as $answer) {
            $this->answers[$answer->question_id] = $answer->question->type === 'multi_choice'
                ? (json_decode($answer->value, true) ?? [])
                : $answer->value;
        }

        $this->activeCategoryId = $this->cycle->categories()->orderBy('sort_order')->value('id');
    }

    public function getCategoriesProperty()
    {
        if (! $this->cycle) {
            return collect();
        }

        return $this->cycle->categories()->with('questions')->orderBy('sort_order')->get();
    }

    /**
     * answered/total count per category, used for the tab chips.
     */
    public function getCategoryProgressProperty(): array
    {
        $progress = [];

        foreach ($this->categories as $category) {
            $total = $category->questions->count();
            $answered = $category->questions->filter(function ($q) {
                $val = $this->answers[$q->id] ?? null;
                return $val !== null && $val !== '' && $val !== [];
            })->count();

            $progress[$category->id] = ['answered' => $answered, 'total' => $total];
        }

        return $progress;
    }

    public function getActiveCategoryProperty()
    {
        return $this->categories->firstWhere('id', $this->activeCategoryId);
    }

    public function getOverallProgressProperty(): array
    {
        $totalQuestions = $this->cycle?->questions()->count() ?? 0;
        $answered = collect($this->answers)->filter(fn ($v) => $v !== null && $v !== '' && $v !== [])->count();

        return [
            'answered' => $answered,
            'total' => $totalQuestions,
            'percent' => $totalQuestions > 0 ? (int) round(($answered / $totalQuestions) * 100) : 0,
        ];
    }

    public function goToCategory(int $categoryId): void
    {
        $this->persistAnswers();
        $this->activeCategoryId = $categoryId;
    }

    public function nextCategory(): void
    {
        $ids = $this->categories->pluck('id')->toArray();
        $currentIndex = array_search($this->activeCategoryId, $ids);

        if ($currentIndex !== false && isset($ids[$currentIndex + 1])) {
            $this->goToCategory($ids[$currentIndex + 1]);
        }
    }

    public function previousCategory(): void
    {
        $ids = $this->categories->pluck('id')->toArray();
        $currentIndex = array_search($this->activeCategoryId, $ids);

        if ($currentIndex !== false && isset($ids[$currentIndex - 1])) {
            $this->goToCategory($ids[$currentIndex - 1]);
        }
    }

    public function saveDraft(): void
    {
        $this->persistAnswers();
        $this->statusType = 'success';
        $this->statusMessage = 'Draft saved.';
    }

    public function submit(): void
    {
        if (! $this->submission || ! $this->cycle?->isOpen()) {
            $this->statusType = 'error';
            $this->statusMessage = 'This survey cycle is not currently open.';
            return;
        }

        $requiredQuestionIds = $this->cycle->questions()->where('is_required', true)->pluck('id');
        $missing = $requiredQuestionIds->filter(function ($id) {
            $val = $this->answers[$id] ?? null;
            return $val === null || $val === '' || $val === [];
        });

        if ($missing->isNotEmpty()) {
            $this->statusType = 'error';
            $this->statusMessage = 'Please answer all required questions before submitting ('.$missing->count().' remaining).';
            return;
        }

        DB::transaction(function () {
            $this->persistAnswers();
            $this->submission->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);
        });

        $this->statusType = 'success';
        $this->statusMessage = 'Survey submitted successfully.';
    }

    protected function persistAnswers(): void
    {
        foreach ($this->answers as $questionId => $value) {
            $storedValue = is_array($value) ? json_encode($value) : $value;

            Answer::updateOrCreate(
                ['submission_id' => $this->submission->id, 'question_id' => $questionId],
                ['value' => $storedValue]
            );
        }
    }

    public function render()
    {
        return view('livewire.survey.survey-form', [
            'categories' => $this->categories,
            'categoryProgress' => $this->categoryProgress,
            'activeCategory' => $this->activeCategory,
            'overallProgress' => $this->overallProgress,
        ]);
    }
}
