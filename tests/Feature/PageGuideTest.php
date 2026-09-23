<?php

namespace Tests\Feature;

use App\Models\PageGuide;
use App\Models\Role;
use App\Models\User;
use App\Services\PageGuideService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageGuideTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Superuser', 'description' => 'All access']);
        $this->admin = User::factory()->create(['password_changed_at' => now()]);
        $this->admin->roles()->attach($role->id);
    }

    public function test_shipped_guide_is_shared_on_its_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.index'))
            ->assertInertia(fn ($page) => $page
                ->where('pageGuide', fn ($v) => str_contains($v, 'Configuration Centre'))
            );
    }

    public function test_db_override_wins_over_shipped_file(): void
    {
        PageGuide::create([
            'key' => 'admin.settings.index',
            'content' => '# House rules override',
        ]);

        $this->assertSame(
            '# House rules override',
            app(PageGuideService::class)->contentFor('admin.settings.index')
        );
    }

    public function test_page_without_guide_shares_null(): void
    {
        $this->assertNull(app(PageGuideService::class)->contentFor('some.unknown.route'));
    }

    public function test_docs_viewer_renders_a_doc(): void
    {
        $this->actingAs($this->admin)
            ->get('/help/docs/glossary.md')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Help/DocViewer')
                ->where('path', 'glossary.md')
                ->where('content', fn ($v) => str_contains($v, 'AVCO'))
            );
    }

    public function test_docs_viewer_blocks_path_traversal(): void
    {
        $this->actingAs($this->admin)
            ->get('/help/docs/..%2f.env')
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->get('/help/docs/../composer.json')
            ->assertNotFound();
    }
}
