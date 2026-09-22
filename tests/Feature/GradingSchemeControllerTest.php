<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\GradingScheme;
use App\Models\GradingRule;

class GradingSchemeControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user with admin privileges
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_list_grading_schemes()
    {
        // Create test grading schemes
        GradingScheme::factory()->count(3)->create();

        $response = $this->getJson(route('academic.exam-management.api.grading-schemes.index'));

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'name',
                                'type',
                                'is_active',
                                'grading_rules'
                            ]
                        ]
                    ]
                ]);
    }

    /** @test */
    public function it_can_create_a_grading_scheme()
    {
        $gradingSchemeData = [
            'name' => 'Test Grading Scheme',
            'type' => 'letter',
            'description' => 'A test grading scheme',
            'is_active' => true,
            'rules' => [
                [
                    'grade_label' => 'A',
                    'min_threshold' => 90,
                    'max_threshold' => 100,
                    'grade_point' => 4.0,
                    'description' => 'Excellent'
                ],
                [
                    'grade_label' => 'B',
                    'min_threshold' => 80,
                    'max_threshold' => 89,
                    'grade_point' => 3.0,
                    'description' => 'Good'
                ],
                [
                    'grade_label' => 'C',
                    'min_threshold' => 70,
                    'max_threshold' => 79,
                    'grade_point' => 2.0,
                    'description' => 'Average'
                ],
                [
                    'grade_label' => 'F',
                    'min_threshold' => 0,
                    'max_threshold' => 69,
                    'grade_point' => 0.0,
                    'description' => 'Fail'
                ]
            ]
        ];

        $response = $this->postJson(route('academic.exam-management.api.grading-schemes.store'), $gradingSchemeData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'name',
                        'type',
                        'grading_rules'
                    ]
                ]);

        $this->assertDatabaseHas('grading_schemes', [
            'name' => 'Test Grading Scheme',
            'type' => 'letter'
        ]);

        $this->assertDatabaseHas('grading_rules', [
            'grade_label' => 'A',
            'min_threshold' => 90,
            'max_threshold' => 100
        ]);
    }

    /** @test */
    public function it_validates_grading_scheme_creation()
    {
        $invalidData = [
            'name' => '', // Required field missing
            'type' => 'invalid_type', // Invalid type
            'rules' => [] // Empty rules array
        ];

        $response = $this->postJson(route('academic.exam-management.api.grading-schemes.store'), $invalidData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'type', 'rules']);
    }

    /** @test */
    public function it_validates_grading_rules_for_overlaps()
    {
        $gradingSchemeData = [
            'name' => 'Test Grading Scheme',
            'type' => 'letter',
            'rules' => [
                [
                    'grade_label' => 'A',
                    'min_threshold' => 90,
                    'max_threshold' => 100,
                    'grade_point' => 4.0
                ],
                [
                    'grade_label' => 'B',
                    'min_threshold' => 85, // Overlaps with A grade
                    'max_threshold' => 95,
                    'grade_point' => 3.0
                ]
            ]
        ];

        $response = $this->postJson(route('academic.exam-management.api.grading-schemes.store'), $gradingSchemeData);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors'
                ]);
    }

    /** @test */
    public function it_can_show_a_grading_scheme()
    {
        $gradingScheme = GradingScheme::factory()
            ->has(GradingRule::factory()->count(3))
            ->create();

        $response = $this->getJson(route('academic.exam-management.api.grading-schemes.show', $gradingScheme));

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'grading_scheme' => [
                            'id',
                            'name',
                            'type',
                            'grading_rules'
                        ],
                        'analytics'
                    ]
                ]);
    }

    /** @test */
    public function it_can_update_a_grading_scheme()
    {
        $gradingScheme = GradingScheme::factory()
            ->has(GradingRule::factory()->count(2))
            ->create();

        $updateData = [
            'name' => 'Updated Grading Scheme',
            'description' => 'Updated description',
            'is_active' => false
        ];

        $response = $this->putJson(route('academic.exam-management.api.grading-schemes.update', $gradingScheme), $updateData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data'
                ]);

        $this->assertDatabaseHas('grading_schemes', [
            'id' => $gradingScheme->id,
            'name' => 'Updated Grading Scheme',
            'is_active' => false
        ]);
    }

    /** @test */
    public function it_can_delete_a_grading_scheme()
    {
        $gradingScheme = GradingScheme::factory()
            ->has(GradingRule::factory()->count(2))
            ->create();

        $response = $this->deleteJson(route('academic.exam-management.api.grading-schemes.destroy', $gradingScheme));

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);

        $this->assertDatabaseMissing('grading_schemes', [
            'id' => $gradingScheme->id
        ]);
    }

    /** @test */
    public function it_can_validate_grading_rules()
    {
        $rules = [
            [
                'grade_label' => 'A',
                'min_threshold' => 90,
                'max_threshold' => 100,
                'grade_point' => 4.0
            ],
            [
                'grade_label' => 'B',
                'min_threshold' => 80,
                'max_threshold' => 89,
                'grade_point' => 3.0
            ],
            [
                'grade_label' => 'F',
                'min_threshold' => 0,
                'max_threshold' => 79,
                'grade_point' => 0.0
            ]
        ];

        $response = $this->postJson(route('academic.exam-management.api.grading-schemes.validate-rules'), [
            'rules' => $rules
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'valid',
                    'errors'
                ]);
    }

    /** @test */
    public function it_can_calculate_grade()
    {
        $gradingScheme = GradingScheme::factory()->create();

        // Create grading rules
        GradingRule::factory()->create([
            'grading_scheme_id' => $gradingScheme->id,
            'grade_label' => 'A',
            'min_threshold' => 90,
            'max_threshold' => 100,
            'grade_point' => 4.0
        ]);

        GradingRule::factory()->create([
            'grading_scheme_id' => $gradingScheme->id,
            'grade_label' => 'B',
            'min_threshold' => 80,
            'max_threshold' => 89,
            'grade_point' => 3.0
        ]);

        $response = $this->postJson(route('academic.exam-management.api.grading-schemes.calculate-grade', $gradingScheme), [
            'marks' => 85,
            'total_marks' => 100
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'grade',
                        'grade_point',
                        'percentage'
                    ]
                ]);
    }

    /** @test */
    public function it_can_duplicate_a_grading_scheme()
    {
        $gradingScheme = GradingScheme::factory()
            ->has(GradingRule::factory()->count(3))
            ->create();

        $response = $this->postJson(route('academic.exam-management.api.grading-schemes.duplicate', $gradingScheme), [
            'name' => 'Duplicated Grading Scheme'
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data'
                ]);

        $this->assertDatabaseHas('grading_schemes', [
            'name' => 'Duplicated Grading Scheme'
        ]);

        // Check that rules were duplicated
        $originalRulesCount = $gradingScheme->gradingRules()->count();
        $duplicatedScheme = GradingScheme::where('name', 'Duplicated Grading Scheme')->first();
        $duplicatedRulesCount = $duplicatedScheme->gradingRules()->count();

        $this->assertEquals($originalRulesCount, $duplicatedRulesCount);
    }

    /** @test */
    public function it_can_toggle_grading_scheme_status()
    {
        $gradingScheme = GradingScheme::factory()->create(['is_active' => true]);

        $response = $this->patchJson(route('academic.exam-management.api.grading-schemes.toggle-status', $gradingScheme));

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data'
                ]);

        $this->assertDatabaseHas('grading_schemes', [
            'id' => $gradingScheme->id,
            'is_active' => false
        ]);
    }
}
