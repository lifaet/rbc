<?php
namespace Tests\Feature;
use App\Models\Category; use App\Models\Cycle; use App\Models\Factory; use App\Models\Submission; use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase; use Illuminate\Support\Facades\Hash; use Livewire\Livewire; use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    private function world(): array
    {
        $factory = Factory::create(['name' => 'Test Factory', 'contact_email' => 'f@example.com', 'status' => 'active']);
        $factoryUser = User::create(['name' => 'Factory User', 'email' => 'factory@example.com', 'password' => Hash::make('password'), 'role' => 'factory_user', 'factory_id' => $factory->id]);
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => Hash::make('password'), 'role' => 'super_admin']);
        $cycle = Cycle::create(['name' => '2026 H1', 'open_date' => now()->subDay(), 'close_date' => now()->addMonth(), 'status' => 'open']);
        $cat = Category::create(['cycle_id' => $cycle->id, 'name' => 'Environmental', 'pillar' => 'environmental']);
        $q = $cat->questions()->create(['cycle_id' => $cycle->id, 'text' => 'Has ETP?', 'type' => 'single_choice', 'options' => ['Yes', 'No'], 'is_required' => true]);
        return compact('factory', 'factoryUser', 'admin', 'cycle', 'cat', 'q');
    }

    public function test_guest_is_redirected_to_login(): void { $this->get('/survey')->assertRedirect('/login'); $this->get('/')->assertRedirect('/login'); }

    public function test_removed_pages_do_not_exist(): void
    {
        $w = $this->world();
        $this->actingAs($w['admin'])->get('/admin/submissions')->assertNotFound();
        $this->actingAs($w['factoryUser'])->get('/documents')->assertNotFound();
    }

    public function test_factory_cannot_access_admin_pages(): void
    {
        $w = $this->world();
        $this->actingAs($w['factoryUser'])->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($w['factoryUser'])->get('/admin/users')->assertForbidden();
        $this->actingAs($w['factoryUser'])->get('/admin/exports/submissions/excel')->assertForbidden();
    }

    public function test_factory_can_save_draft_and_submit(): void
    {
        $w = $this->world();
        Livewire::actingAs($w['factoryUser'])->test(\App\Livewire\Survey\SurveyForm::class)
            ->set('answers.'.$w['q']->id, 'Yes')->call('saveDraft')->assertSet('statusType', 'success');
        $this->assertSame('draft', Submission::first()->status);
        Livewire::actingAs($w['factoryUser'])->test(\App\Livewire\Survey\SurveyForm::class)
            ->call('submit')->assertSet('statusType', 'success');
        $this->assertSame('submitted', Submission::first()->status);
        $this->assertNotNull(Submission::first()->submitted_at);
    }

    public function test_admin_can_export_excel_and_pdf(): void
    {
        $w = $this->world();
        $this->actingAs($w['admin'])->get('/admin/exports/submissions/excel')->assertOk();
        $this->actingAs($w['admin'])->get('/admin/exports/factories/'.$w['factory']->id.'/excel')->assertOk();
        $this->actingAs($w['admin'])->get('/admin/exports/factories/'.$w['factory']->id.'/pdf')->assertOk();
    }

    public function test_inactive_factory_user_cannot_login(): void
    {
        $w = $this->world(); $w['factory']->update(['status' => 'inactive']);
        $this->post('/login', ['email' => 'factory@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivating_factory_disables_its_users(): void
    {
        $w = $this->world();
        Livewire::actingAs($w['admin'])->test(\App\Livewire\Admin\FactoryManager::class)->call('toggleStatus', $w['factory']->id);
        $this->assertFalse($w['factoryUser']->fresh()->is_active);
    }

    public function test_password_reset_page_loads(): void { $this->get('/forgot-password')->assertOk(); }
}
