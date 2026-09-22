<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\GradingService;
use App\Models\GradingScheme;
use App\Models\GradingRule;
use App\Models\Assessment;
use App\Models\StudentMark;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

class GradingServiceTest extends TestCase
{
    use RefreshDatabase;

    private GradingService $gradingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gradingService = new GradingService();
    }

    /** @test */
    public function it_validates_grading_scheme_rules_successfully()
    {
        $validRules = [
            [
                'grade_label' => 'A',
                'min_threshold' => 90,
                'max_threshold' => 100,
                'grade_point' => 4.0,
            ],
            [
                'grade_label' => 'B',
                'min_threshold' => 80,
                'max_threshold' => 89.99,
                'grade_point' => 3.0,
            ],
            [
                'grade_label' => 'C',
                'min_threshold' => 70,
                'max_threshold' => 79.99,
                'grade_point' => 2.0,
            ],
            [
                'grade_label' => 'F',
                'min_threshold' => 0,
                'max_threshold' => 69.99,
                'grade_point' => 0.0,
            ],
        ];

        $errors = $this->gradingService->validateGradingScheme($validRules);
        $this->assertEmpty($errors);
    }

    /** @test */
    public function it_detects_overlapping_grading_rules()
    {
        $overlappingRules = [
            [
                'grade_label' => 'A',
                'min_threshold' => 90,
                'max_threshold' => 100,
                'grade_point' => 4.0,
            ],
            [
                'grade_label' => 'B',
                'min_threshold' => 85, // Overlaps with A
                'max_threshold' => 95,
                'grade_point' => 3.0,
            ],
        ];

        $errors = $this->gradingService->validateGradingScheme($overlappingRules);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('overlaps', strtolower($errors[0]));
    }

    /** @test */
    public function it_detects_gaps_in_grading_rules()
    {
        $gappedRules = [
            [
                'grade_label' => 'A',
                'min_threshold' => 90,
                'max_threshold' => 100,
                'grade_point' => 4.0,
            ],
            [
                'grade_label' => 'B',
                'min_threshold' => 70, // Gap between 70-90
                'max_threshold' => 80,
                'grade_point' => 3.0,
            ],
        ];

        $errors = $this->gradingService->validateGradingScheme($gappedRules);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('gap', strtolower($errors[0]));
    }

    /** @test */
    public function it_detects_incomplete_coverage()
    {
        $incompleteCoverageRules = [
            [
                'grade_label' => 'A',
                'min_threshold' => 90,
                'max_threshold' => 100,
                'grade_point' => 4.0,
            ],
            [
                'grade_label' => 'B',
                'min_threshold' => 10, // Doesn't start from 0
                'max_threshold' => 89.99,
                'grade_point' => 3.0,
            ],
        ];

        $errors = $this->gradingService->validateGradingScheme($incompleteCoverageRules);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('start from 0%', $errors[0]);
    }

    /** @test */
    public function it_validates_individual_grading_rules()
    {
        $invalidRules = [
            [
                'grade_label' => '', // Empty label
                'min_threshold' => 'invalid', // Non-numeric
                'max_threshold' => 50,
                'grade_point' => -1, // Negative grade point
            ],
            [
                'grade_label' => 'B',
                'min_threshold' => 80,
                'max_threshold' => 70, // Min > Max
                'grade_point' => 3.0,
            ],
        ];

        $errors = $this->gradingService->validateGradingScheme($invalidRules);
        $this->assertNotEmpty($errors);
        $this->assertGreaterThan(3, count($errors)); // Should have multiple validation errors
    }

    /** @test */
    public function it_calculates_grade_correctly()
    {
        // Create a grading scheme with rules
        $gradingScheme = GradingScheme::factory()->create();

        GradingRule::factory()->create([
            'grading_scheme_id' => $gradingScheme->id,
            'grade_label' => 'A',
            'min_threshold' => 90,
            'max_threshold' => 100,
            'grade_point' => 4.0,
        ]);

        GradingRule::factory()->create([
            'grading_scheme_id' => $gradingScheme->id,
            'grade_label' => 'B',
            'min_threshold' => 80,
            'max_threshold' => 89.99,
            'grade_point' => 3.0,
        ]);

        // Test grade calculation
        $result = $this->gradingService->calculateGrade(85, 100, $gradingScheme);

        $this->assertNotNull($result);
        $this->assertEquals('B', $result['grade']);
        $this->assertEquals(3.0, $result['grade_point']);
        $this->assertEquals(85, $result['percentage']);
    }

    /** @test */
    public function it_throws_exception_for_invalid_marks()
    {
        $gradingScheme = GradingScheme::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->gradingService->calculateGrade(-10, 100, $gradingScheme);
    }

    /** @test */
    public function it_throws_exception_for_invalid_total_marks()
    {
        $gradingScheme = GradingScheme::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->gradingService->calculateGrade(50, 0, $gradingScheme);
    }

    /** @test */
    public function it_calculates_statistical_measures_correctly()
    {
        // Create test data
        $assessment = Assessment::factory()->create(['total_marks' => 100]);

        // Create student marks
        StudentMark::factory()->create([
            'assessment_id' => $assessment->id,
            'marks_obtained' => 85,
            'status' => 'present'
        ]);

        StudentMark::factory()->create([
            'assessment_id' => $assessment->id,
            'marks_obtained' => 90,
            'status' => 'present'
        ]);

        StudentMark::factory()->create([
            'assessment_id' => $assessment->id,
            'marks_obtained' => 75,
            'status' => 'present'
        ]);

        $distribution = $this->gradingService->getGradeDistribution($assessment);

        $this->assertArrayHasKey('statistics', $distribution);
        $this->assertArrayHasKey('mean', $distribution['statistics']);
        $this->assertArrayHasKey('median', $distribution['statistics']);
        $this->assertArrayHasKey('standard_deviation', $distribution['statistics']);

        // Test statistical calculations
        $stats = $distribution['statistics'];
        $this->assertEquals(83.33, $stats['mean']); // (85+90+75)/3
        $this->assertEquals(85, $stats['median']);
        $this->assertEquals(90, $stats['highest_score']);
        $this->assertEquals(75, $stats['lowest_score']);
    }

    /** @test */
    public function it_creates_grading_scheme_with_validation()
    {
        $schemeData = [
            'name' => 'Test Scheme',
            'type' => 'letter',
            'description' => 'Test grading scheme',
            'is_active' => true,
        ];

        $rules = [
            [
                'grade_label' => 'A',
                'min_threshold' => 90,
                'max_threshold' => 100,
                'grade_point' => 4.0,
            ],
            [
                'grade_label' => 'F',
                'min_threshold' => 0,
                'max_threshold' => 89.99,
                'grade_point' => 0.0,
            ],
        ];

        $gradingScheme = $this->gradingService->createGradingScheme($schemeData, $rules);

        $this->assertInstanceOf(GradingScheme::class, $gradingScheme);
        $this->assertEquals('Test Scheme', $gradingScheme->name);
        $this->assertCount(2, $gradingScheme->gradingRules);
    }

    /** @test */
    public function it_throws_validation_exception_for_invalid_scheme_creation()
    {
        $schemeData = [
            'name' => 'Invalid Scheme',
            'type' => 'letter',
        ];

        $invalidRules = [
            [
                'grade_label' => 'A',
                'min_threshold' => 90,
                'max_threshold' => 80, // Invalid: min > max
                'grade_point' => 4.0,
            ],
        ];

        $this->expectException(ValidationException::class);
        $this->gradingService->createGradingScheme($schemeData, $invalidRules);
    }

    /** @test */
    public function it_calculates_weighted_average_correctly()
    {
        // Create assessments with different weightages
        $assessment1 = Assessment::factory()->create([
            'total_marks' => 100,
            'weightage' => 60, // 60% weight
        ]);

        $assessment2 = Assessment::factory()->create([
            'total_marks' => 50,
            'weightage' => 40, // 40% weight
        ]);

        // Create student marks
        $mark1 = StudentMark::factory()->create([
            'assessment_id' => $assessment1->id,
            'marks_obtained' => 80, // 80%
            'status' => 'present'
        ]);

        $mark2 = StudentMark::factory()->create([
            'assessment_id' => $assessment2->id,
            'marks_obtained' => 40, // 80%
            'status' => 'present'
        ]);

        $marks = collect([$mark1, $mark2]);
        $result = $this->gradingService->calculateWeightedAverage($marks);

        $this->assertNotNull($result);
        $this->assertEquals(80, $result['weighted_average']); // (80*0.6 + 80*0.4) = 80
        $this->assertEquals(100, $result['total_weight']);
        $this->assertEquals(2, $result['assessment_count']);
    }
}
