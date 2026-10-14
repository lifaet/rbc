<?php

namespace App\Livewire\Admin;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('components.layout')]
#[Title('Manage Factories')]
class FactoryManager extends Component
{
    use WithFileUploads, WithPagination;
    public $csvFile;

    public string $search = '';

    // Form state (shared for add/edit)
    public ?int $editingId = null;
    public string $name = '';
    public string $region = '';
    public string $contactEmail = '';
    public string $status = 'active';

    // Login account fields — only used when creating a factory that needs a new user
    public string $loginEmail = '';
    public string $loginPassword = '';

    public bool $showForm = false;

    protected function rules(): array
    {
        $emailRule = 'nullable|email|max:255';

        return [
            'name' => 'required|string|max:150',
            'region' => 'nullable|string|max:100',
            'contactEmail' => $emailRule,
            'status' => 'required|in:active,inactive',
            'loginEmail' => $this->editingId ? 'nullable|email|max:255' : 'nullable|email|max:255|unique:users,email',
            'loginPassword' => $this->editingId ? 'nullable|min:6' : 'nullable|min:6',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEditForm(int $factoryId): void
    {
        $factory = Factory::findOrFail($factoryId);

        $this->editingId = $factory->id;
        $this->name = $factory->name;
        $this->region = $factory->region ?? '';
        $this->contactEmail = $factory->contact_email ?? '';
        $this->status = $factory->status;
        $this->loginEmail = '';
        $this->loginPassword = '';
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $factory = Factory::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $this->name,
                'region' => $this->region ?: null,
                'contact_email' => $this->contactEmail ?: null,
                'status' => $this->status,
            ]
        );

        // Optionally create/update a login account for this factory from the same form.
        if ($this->loginEmail && $this->loginPassword) {
            $existingUser = $factory->users()->first();

            if ($existingUser) {
                $existingUser->update([
                    'email' => $this->loginEmail,
                    'password' => Hash::make($this->loginPassword),
                ]);
            } else {
                User::create([
                    'name' => $this->name.' User',
                    'email' => $this->loginEmail,
                    'password' => Hash::make($this->loginPassword),
                    'role' => 'factory_user',
                    'factory_id' => $factory->id,
                ]);
            }
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function toggleStatus(int $factoryId): void
    {
        $factory = Factory::findOrFail($factoryId);
        $factory->update(['status' => $factory->status === 'active' ? 'inactive' : 'active']);
        // Deactivating a factory also disables its user logins.
        $factory->users()->update(['is_active' => $factory->status === 'active']);
    }

    /** CSV columns: name,region,district,contact_email,contact_person,contact_phone,login_email,login_password */
    public function importCsv(): void
    {
        $this->validate(['csvFile' => 'required|file|mimes:csv,txt|max:2048']);
        $handle = fopen($this->csvFile->getRealPath(), 'r');
        $header = array_map(fn ($h) => strtolower(trim($h)), fgetcsv($handle));
        $imported = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            if (empty($data['name'])) continue;
            $factory = Factory::create(['name'=>$data['name'],'region'=>$data['region']??null,'district'=>$data['district']??null,'contact_email'=>$data['contact_email']??null,'contact_person'=>$data['contact_person']??null,'contact_phone'=>$data['contact_phone']??null,'status'=>'active']);
            if (! empty($data['login_email']) && ! empty($data['login_password'])) {
                User::create(['name'=>$data['name'].' User','email'=>$data['login_email'],'password'=>Hash::make($data['login_password']),'role'=>'factory_user','factory_id'=>$factory->id]);
            }
            $imported++;
        }
        fclose($handle);
        session()->flash('success', "Imported {$imported} factories.");
        $this->reset('csvFile');
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'region', 'contactEmail', 'loginEmail', 'loginPassword']);
        $this->status = 'active';
    }

    public function render()
    {
        $factories = Factory::with('users')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.factory-manager', ['factories' => $factories]);
    }
}
