<?php

namespace App\Livewire\Admin;

use App\Models\Cycle;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layout')]
#[Title('Survey Cycles')]
class CycleManager extends Component
{
    public string $name = '';
    public string $open_date = '';
    public string $close_date = '';
    public ?int $editingId = null;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'open_date' => 'required|date',
            'close_date' => 'required|date|after:open_date',
        ];
    }

    public function save(): void
    {
        $this->validate();

        Cycle::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $this->name,
                'open_date' => $this->open_date,
                'close_date' => $this->close_date,
                'status' => 'draft',
            ]
        );

        $this->reset(['name', 'open_date', 'close_date', 'editingId']);
    }

    public function edit(int $id): void
    {
        $cycle = Cycle::findOrFail($id);
        $this->editingId = $cycle->id;
        $this->name = $cycle->name;
        $this->open_date = $cycle->open_date->format('Y-m-d');
        $this->close_date = $cycle->close_date->format('Y-m-d');
    }

    public function toggleStatus(int $id): void
    {
        $cycle = Cycle::findOrFail($id);
        $cycle->update(['status' => $cycle->status === 'open' ? 'closed' : 'open']);
    }

    public function cloneFromPrevious(int $sourceCycleId): void
    {
        $source = Cycle::with('categories.questions')->findOrFail($sourceCycleId);

        $newCycle = Cycle::create([
            'name' => $source->name.' (Copy)',
            'open_date' => now()->toDateString(),
            'close_date' => now()->addMonths(6)->toDateString(),
            'status' => 'draft',
        ]);

        foreach ($source->categories as $category) {
            $newCategory = $newCycle->categories()->create([
                'name' => $category->name,
                'sort_order' => $category->sort_order,
            ]);

            foreach ($category->questions as $question) {
                $newCategory->questions()->create([
                    'cycle_id' => $newCycle->id,
                    'text' => $question->text,
                    'type' => $question->type,
                    'options' => $question->options,
                    'is_required' => $question->is_required,
                    'sort_order' => $question->sort_order,
                ]);
            }
        }
    }

    public function render()
    {
        return view('livewire.admin.cycle-manager', [
            'cycles' => Cycle::withCount('submissions')->latest('open_date')->get(),
        ]);
    }
}
