<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Cycle;
use App\Models\Question;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layout')]
#[Title('Manage Questions')]
class QuestionManager extends Component
{
    public Cycle $cycle;

    // Category form state
    public string $categoryName = '';
    public ?int $editingCategoryId = null;
    public string $editingCategoryName = '';

    // Question form state (used for both "add" and "edit" — editingQuestionId null = add mode)
    public ?int $activeCategoryId = null;
    public ?int $editingQuestionId = null;
    public string $questionText = '';
    public string $questionType = 'text';
    public string $questionOptions = ''; // comma-separated, for choice types
    public bool $questionRequired = true;

    public function mount(Cycle $cycle): void
    {
        $this->cycle = $cycle;
    }

    // --- Categories ---

    public function addCategory(): void
    {
        $this->validate(['categoryName' => 'required|string|max:100']);

        $this->cycle->categories()->create([
            'name' => $this->categoryName,
            'sort_order' => $this->cycle->categories()->count(),
        ]);

        $this->reset('categoryName');
    }

    public function startEditCategory(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);
        $this->editingCategoryId = $categoryId;
        $this->editingCategoryName = $category->name;
    }

    public function saveEditCategory(): void
    {
        $this->validate(['editingCategoryName' => 'required|string|max:100']);

        Category::findOrFail($this->editingCategoryId)->update(['name' => $this->editingCategoryName]);
        $this->reset(['editingCategoryId', 'editingCategoryName']);
    }

    public function cancelEditCategory(): void
    {
        $this->reset(['editingCategoryId', 'editingCategoryName']);
    }

    public function deleteCategory(int $categoryId): void
    {
        Category::findOrFail($categoryId)->delete();
    }

    public function moveCategory(int $categoryId, string $direction): void
    {
        $categories = $this->cycle->categories()->orderBy('sort_order')->get();
        $this->swapSortOrder($categories, $categoryId, $direction);
    }

    // --- Questions ---

    public function startAddQuestion(int $categoryId): void
    {
        $this->resetQuestionForm();
        $this->activeCategoryId = $categoryId;
    }

    public function startEditQuestion(int $questionId): void
    {
        $question = Question::findOrFail($questionId);

        $this->editingQuestionId = $question->id;
        $this->activeCategoryId = $question->category_id;
        $this->questionText = $question->text;
        $this->questionType = $question->type;
        $this->questionOptions = $question->options ? implode(', ', $question->options) : '';
        $this->questionRequired = $question->is_required;
    }

    public function saveQuestion(): void
    {
        $this->validate([
            'activeCategoryId' => 'required|exists:categories,id',
            'questionText' => 'required|string',
            'questionType' => 'required|in:text,number,single_choice,multi_choice,date',
        ]);

        $options = in_array($this->questionType, ['single_choice', 'multi_choice'])
            ? array_values(array_filter(array_map('trim', explode(',', $this->questionOptions))))
            : null;

        if ($this->editingQuestionId) {
            Question::findOrFail($this->editingQuestionId)->update([
                'category_id' => $this->activeCategoryId,
                'text' => $this->questionText,
                'type' => $this->questionType,
                'options' => $options,
                'is_required' => $this->questionRequired,
            ]);
        } else {
            Question::create([
                'category_id' => $this->activeCategoryId,
                'cycle_id' => $this->cycle->id,
                'text' => $this->questionText,
                'type' => $this->questionType,
                'options' => $options,
                'is_required' => $this->questionRequired,
                'sort_order' => Question::where('category_id', $this->activeCategoryId)->count(),
            ]);
        }

        $this->resetQuestionForm();
    }

    public function cancelQuestionForm(): void
    {
        $this->resetQuestionForm();
    }

    protected function resetQuestionForm(): void
    {
        $this->reset(['editingQuestionId', 'activeCategoryId', 'questionText', 'questionOptions']);
        $this->questionType = 'text';
        $this->questionRequired = true;
    }

    public function deleteQuestion(int $questionId): void
    {
        Question::findOrFail($questionId)->delete();
    }

    public function moveQuestion(int $questionId, string $direction): void
    {
        $question = Question::findOrFail($questionId);
        $siblings = Question::where('category_id', $question->category_id)->orderBy('sort_order')->get();
        $this->swapSortOrder($siblings, $questionId, $direction);
    }

    /**
     * Shared reorder logic for both categories and questions: swap sort_order
     * with the adjacent sibling in the given direction.
     */
    protected function swapSortOrder($collection, int $id, string $direction): void
    {
        $ids = $collection->pluck('id')->toArray();
        $index = array_search($id, $ids);

        if ($index === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if (! isset($ids[$targetIndex])) {
            return;
        }

        $current = $collection[$index];
        $target = $collection[$targetIndex];

        $currentOrder = $current->sort_order;
        $current->update(['sort_order' => $target->sort_order]);
        $target->update(['sort_order' => $currentOrder]);
    }

    public function render()
    {
        return view('livewire.admin.question-manager', [
            'categories' => $this->cycle->categories()->with(['questions' => fn ($q) => $q->orderBy('sort_order')])->orderBy('sort_order')->get(),
        ]);
    }
}
