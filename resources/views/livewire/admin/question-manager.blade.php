<div class="max-w-5xl mx-auto p-6 lg:p-8">
    <h1 class="font-display text-2xl font-bold text-gray-900">Questions — {{ $cycle->name }}</h1>
    <p class="text-sm text-gray-500 mt-1">Manage categories and questions for this cycle.</p>
    <div class="flax-mark"></div>

    <!-- Add category -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-6 mt-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">Add Category</h2>
        <form wire:submit.prevent="addCategory" class="flex gap-3">
            <input type="text" wire:model="categoryName" placeholder="e.g. Energy Management"
                class="flex-1 rounded-lg border-gray-300 focus:ring-brand-600 focus:border-brand-600" />
            <button type="submit" class="px-4 py-2 rounded-lg bg-brand-700 text-white text-sm hover:bg-brand-800">Add</button>
        </form>
        @error('categoryName') <span class="text-alert text-xs">{{ $message }}</span> @enderror
    </div>

    <!-- Question form (add or edit, depending on editingQuestionId) -->
    @if ($activeCategoryId)
        <div class="bg-white rounded-2xl border border-brand-200 shadow-sm p-5 mb-8 ring-1 ring-brand-100">
            <h2 class="text-sm font-semibold text-gray-700 mb-3">
                {{ $editingQuestionId ? 'Edit Question' : 'Add Question' }}
            </h2>
            <form wire:submit.prevent="saveQuestion" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
                    <select wire:model="activeCategoryId" class="w-full rounded-lg border-gray-300">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Question Text</label>
                    <textarea wire:model="questionText" rows="2" class="w-full rounded-lg border-gray-300"></textarea>
                    @error('questionText') <span class="text-alert text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Answer Type</label>
                    <select wire:model.live="questionType" class="w-full rounded-lg border-gray-300">
                        <option value="text">Text</option>
                        <option value="number">Number</option>
                        <option value="date">Date</option>
                        <option value="single_choice">Single Choice</option>
                        <option value="multi_choice">Multiple Choice</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="questionRequired" /> Required
                    </label>
                </div>

                @if (in_array($questionType, ['single_choice', 'multi_choice']))
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Options (comma-separated)</label>
                        <input type="text" wire:model="questionOptions" placeholder="Yes, No, Partially"
                            class="w-full rounded-lg border-gray-300" />
                    </div>
                @endif

                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-brand-700 text-white text-sm hover:bg-brand-800">
                        {{ $editingQuestionId ? 'Save Changes' : 'Add Question' }}
                    </button>
                    <button type="button" wire:click="cancelQuestionForm"
                        class="px-4 py-2 rounded-lg border border-gray-200 text-gray-600 text-sm hover:bg-gray-50">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Category & question list -->
    <div class="space-y-5">
        @foreach ($categories as $category)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5" wire:key="category-{{ $category->id }}">
                <div class="flex justify-between items-center mb-3">
                    @if ($editingCategoryId === $category->id)
                        <form wire:submit.prevent="saveEditCategory" class="flex items-center gap-2 flex-1">
                            <input type="text" wire:model="editingCategoryName" class="rounded-lg border-gray-300 text-sm py-1.5" />
                            <button type="submit" class="text-xs text-brand-700 font-medium hover:underline">Save</button>
                            <button type="button" wire:click="cancelEditCategory" class="text-xs text-gray-500 hover:underline">Cancel</button>
                        </form>
                    @else
                        <h3 class="font-display font-semibold text-gray-900">{{ $category->name }}</h3>
                        <div class="flex items-center gap-3 text-xs">
                            <button wire:click="moveCategory({{ $category->id }}, 'up')" class="text-gray-400 hover:text-brand-700" title="Move up">↑</button>
                            <button wire:click="moveCategory({{ $category->id }}, 'down')" class="text-gray-400 hover:text-brand-700" title="Move down">↓</button>
                            <button wire:click="startEditCategory({{ $category->id }})" class="text-brand-700 hover:underline">Rename</button>
                            <button wire:click="startAddQuestion({{ $category->id }})" class="text-brand-700 hover:underline">+ Question</button>
                            <button wire:click="deleteCategory({{ $category->id }})"
                                wire:confirm="Delete this category and all its questions?"
                                class="text-alert hover:underline">Delete</button>
                        </div>
                    @endif
                </div>

                <ul class="divide-y divide-gray-100">
                    @foreach ($category->questions as $question)
                        <li class="py-2.5 flex justify-between items-start gap-3" wire:key="question-{{ $question->id }}">
                            <div>
                                <p class="text-sm text-gray-800">{{ $question->text }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ ucfirst(str_replace('_', ' ', $question->type)) }}
                                    @if ($question->is_required) · required @endif
                                    @if ($question->options) · {{ implode(', ', $question->options) }} @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-2.5 text-xs shrink-0">
                                <button wire:click="moveQuestion({{ $question->id }}, 'up')" class="text-gray-400 hover:text-brand-700">↑</button>
                                <button wire:click="moveQuestion({{ $question->id }}, 'down')" class="text-gray-400 hover:text-brand-700">↓</button>
                                <button wire:click="startEditQuestion({{ $question->id }})" class="text-brand-700 hover:underline">Edit</button>
                                <button wire:click="deleteQuestion({{ $question->id }})"
                                    wire:confirm="Delete this question?"
                                    class="text-alert hover:underline">Remove</button>
                            </div>
                        </li>
                    @endforeach
                    @if ($category->questions->isEmpty())
                        <li class="py-2 text-xs text-gray-400">No questions yet.</li>
                    @endif
                </ul>
            </div>
        @endforeach

        @if ($categories->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 p-10 text-center text-sm text-gray-400">
                No categories yet — add one above to get started.
            </div>
        @endif
    </div>
</div>
