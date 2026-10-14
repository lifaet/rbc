<?php

namespace App\Livewire\Profile;

use App\Models\Factory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layout')]
#[Title('My Profile')]
class EditProfile extends Component
{
    public string $name = '';
    public string $email = '';

    public string $currentPassword = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';

    // Factory-specific fields (only shown/editable for factory_user role)
    public string $factoryContactEmail = '';
    public string $factoryRegion = '';

    public string $statusMessage = '';
    public string $statusType = 'info';

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;

        if ($user->isFactoryUser() && $user->factory) {
            $this->factoryContactEmail = $user->factory->contact_email ?? '';
            $this->factoryRegion = $user->factory->region ?? '';
        }
    }

    public function updateProfile(): void
    {
        $user = Auth::user();

        $this->validate([
            'name' => 'required|string|max:150',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'factoryRegion' => 'nullable|string|max:100',
            'factoryContactEmail' => 'nullable|email|max:255',
        ]);

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        if ($user->isFactoryUser() && $user->factory) {
            $user->factory->update([
                'region' => $this->factoryRegion ?: null,
                'contact_email' => $this->factoryContactEmail ?: null,
            ]);
        }

        $this->statusType = 'success';
        $this->statusMessage = 'Profile updated successfully.';
    }

    public function updatePassword(): void
    {
        $user = Auth::user();

        $this->validate([
            'currentPassword' => 'required',
            'newPassword' => 'required|min:6|same:newPasswordConfirmation',
        ]);

        if (! Hash::check($this->currentPassword, $user->password)) {
            $this->addError('currentPassword', 'Current password is incorrect.');
            return;
        }

        $user->update(['password' => Hash::make($this->newPassword)]);

        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->statusType = 'success';
        $this->statusMessage = 'Password updated successfully.';
    }

    public function render()
    {
        return view('livewire.profile.edit-profile', [
            'isFactoryUser' => Auth::user()->isFactoryUser(),
        ]);
    }
}
