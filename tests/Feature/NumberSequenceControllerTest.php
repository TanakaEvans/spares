<?php

namespace Tests\Feature;

use App\Models\NumberSequence;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NumberSequenceControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Superuser', 'description' => 'All access']);
        $this->admin = User::factory()->create(['password_changed_at' => now()]);
        $this->admin->roles()->attach($role->id);

        (new \Database\Seeders\NumberSequenceSeeder)->run();
    }

    public function test_page_renders_with_seeded_sequences_and_previews(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.sequences.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Sequences/Index')
                ->has('sequences', 20)
                ->where('sequences.0.preview', fn ($v) => str_contains($v, '-'))
            );
    }

    public function test_updating_a_sequence_format(): void
    {
        $sequence = NumberSequence::where('type', 'invoice')->first();

        $this->actingAs($this->admin)
            ->patch(route('admin.sequences.update', $sequence), [
                'prefix' => 'TAX',
                'include_date' => true,
                'date_format' => 'Ym',
                'padding' => 5,
                'reset_frequency' => 'yearly',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $sequence->refresh();
        $this->assertSame('TAX', $sequence->prefix);
        $this->assertSame('Ym', $sequence->date_format);
        $this->assertSame(5, $sequence->padding);
    }

    public function test_invalid_prefix_rejected(): void
    {
        $sequence = NumberSequence::where('type', 'invoice')->first();

        $this->actingAs($this->admin)
            ->patch(route('admin.sequences.update', $sequence), [
                'prefix' => 'inv!',
                'include_date' => true,
                'date_format' => 'Ymd',
                'padding' => 4,
                'reset_frequency' => 'never',
            ])
            ->assertSessionHasErrors('prefix');
    }
}
