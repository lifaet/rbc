<?php
namespace App\Livewire\Admin;
use App\Models\Factory; use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout; use Livewire\Attributes\Title; use Livewire\Component; use Livewire\WithPagination;
#[Layout('components.layout')] #[Title('Manage Users')]
class UserManager extends Component {
    use WithPagination;
    public string $search = ''; public ?int $editingId = null;
    public string $name = ''; public string $email = ''; public string $password = ''; public string $role = 'admin'; public ?int $factoryId = null; public bool $showForm = false;
    protected function rules(): array {
        return ['name'=>'required|string|max:120','email'=>'required|email|max:255|unique:users,email,'.($this->editingId ?? 'NULL'),
            'password'=>$this->editingId ? 'nullable|min:8' : 'required|min:8',
            'role'=>'required|in:super_admin,admin,factory_user','factoryId'=>'nullable|exists:factories,id'];
    }
    public function openCreate(): void { $this->reset(['editingId','name','email','password']); $this->role='admin'; $this->factoryId=null; $this->showForm=true; }
    public function edit(int $id): void { $u=User::findOrFail($id); $this->editingId=$u->id; $this->name=$u->name; $this->email=$u->email; $this->role=$u->role; $this->factoryId=$u->factory_id; $this->password=''; $this->showForm=true; }
    public function save(): void {
        $this->validate();
        $data = ['name'=>$this->name,'email'=>$this->email,'role'=>$this->role,'factory_id'=>$this->role==='factory_user' ? $this->factoryId : null];
        if ($this->password) $data['password'] = Hash::make($this->password);
        User::updateOrCreate(['id'=>$this->editingId], $data);
        $this->showForm = false;
    }
    public function toggleActive(int $id): void { $u=User::findOrFail($id); if ($u->id === auth()->id()) return; $u->update(['is_active'=>!$u->is_active]); }
    public function render() {
        return view('livewire.admin.user-manager', [
            'users'=>User::with('factory')->when($this->search, fn($q)=>$q->where('name','like',"%{$this->search}%")->orWhere('email','like',"%{$this->search}%"))->orderBy('name')->paginate(15),
            'factories'=>Factory::orderBy('name')->get(),
        ]);
    }
}
