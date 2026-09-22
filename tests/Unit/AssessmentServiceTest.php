<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\RefreshDatabase;
use App\Services\AssessmentService;
use App\Services\GradingService;
use App\Models\Assessment;
use App\Models\StudentMark;
use App\Models\GradingScheme;
use App\Models\GradingRule;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Validation\ValidationException;

class AssessmentServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected AssessmentService $assessmentService;
    protected GradingService $gradingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gradingService = new GradingService();
        $this->assessmentService = new AssessmentService($this->gradingService);
    }

    /** @test */
    public function it_can_create_an_assessment()
    {
        $gradingScheme = GradingScheme::factory()
            ->has(GradingRule::factory()->count(3))
            ->create(['is_active' => true]);

        $assessmentData = [
            'name' => 'Mathematics Test 1',
            'type' => 'test',
            'subject_id' => 1,
            'grade_level_id' => 1,
            'grading_scheme_id' => $gradingScheme->id,
            'academic_year_id' => 1,
            'total_marks' => 100,
            'weightage' => 20,
            'status' => 'draft',
            'created_by' => User::factory()->create()->id,
        ];

        $assessment = $this->assessmentService->createAssessment($assessmentData);

        $this->assertInstanceOf(Assessment::class, $assessment);
        $this->assertEquals('Mathematics Test 1', $assessment->name);
        $this->assertEquals('test', $assessment->type);
        $this->assertEquals(100, $assessment->total_marks);
    }

    /** @test */
    public function it_validates_assessment_data_on_creation()
    {
        $this->expectException(ValidationException::class);

        $invalidData = [
            'name' => '', // Required field missing
            'type' => 'invalid_type', // Invalid type
            'total_marks' => -10, // Invalid value
        ];

        $this->assessmentService->createAssessment($invalidData);
    }

    /** @test */
    public function it_can_update_an_assessment()
    {
        $gradingScheme = GradingScheme::factory()
            ->has(GradingRule::factory()->count(3))
            ->create(['is_active' => true]);

        $assessment = Assessment::factory()->create([
            'grading_scheme_id' => $gradingScheme->id
        ]);

        $updateData = [
            'name' => 'Updated Assessment Name',
            'total_marks' => 150,
            'weightage' => 25,
        ];

        $updatedAssessment = $this->assessmentService->updateAssessment($assessment, $updateData);

        $this->assertEquals('Updated Assessment Name', $updatedAssessment->name);
        $this->assertEquals(150, $updatedAssessment->total_marks);
        $this->assertEquals(25, $updatedAssessment->weightage);
    }

    /** @test */
    public function it_can_create_student_mark_records()
    {
        $assessment = Assessment::factory()->create();
        $studentIds = [1, 2, 3, 4, 5];

        $studentMarks = $this->assessmentService->createStudentMarkRecords($assessment, $studentIds);

        $this->assertCount(5, $studentMarks);
        $this->assertEquals(5, $assessment->studentMarks()->count());

        foreach ($studentMarks as $mark) {
            $this->assertEquals($assessment->id, $mark->assessment_id);
            $this->assertEquals('present', $mark->status);
        }
    }

    /** @test */
    public function it_can_update_student_marks()
    {
        $gradingScheme = GradingScheme::factory()
            ->has(GradingRule::factory()->count(3))
            ->create(['is_active' => true]);

        $assessment = Assessment::factory()->create([
            'grading_scheme_id' => $gradingScheme->id,
            'total_marks' => 100
        ]);

        $markData = [
            'student_id' => 1,
            'marks_obtained' => 85,
            'status' => 'present',
            'remarks' => 'Good performance'
        ];

        $studentMark = $this->assessmentService->updateStudentMark($assessment, $markData);

        $this->assertEquals(85, $studentMark->marks_obtained);
        $this->assertEquals('present', $studentMark->status);
        $this->assertEquals('Good performance', $studentMark->remarks);
    }

    /** @test */
    public function it_validates_mark_data()
    {
        $assessment = Assessment::factory()->create(['total_marks' => 100]);

        $this->expectException(ValidationException::class);

        $invalidMarkData = [
            'student_id' => 1,
            'marks_obtained' => 150, // Exceeds total marks
            'status' => 'present'
        ];

        $this->assessmentService->updateStudentMark($assessment, $invalidMarkData);
    }

    /** @test */
    public function it_requires_marks_for_present_students()
    {
        $assessment = Assessment::factory()->create();

        $this->expectException(ValidationException::class);

        $invalidMarkData = [
            'student_id' => 1,
            'status' => 'present'
            // Missing marks_obtained
        ];

        $this->assessmentService->updateStudentMark($assessment, $invalidMarkData);
    }

    /** @test */
    public function it_prevents_marks_for_absent_students()
    {
        $assessment = Assessment::factory()->create();

        $this->expectException(ValidationException::class);

        $invalidMarkData = [
            'student_id' => 1,
            'marks_obtained' => 85, // Should not have marks
            'status' => 'absent'
        ];

        $this->assessmentService->updateStudentMark($assessment, $invalidMarkData);
    }

    /** @test */
    public function it_can_bulk_update_marks()
    {
        $gradingScheme = GradingScheme::factory()
            ->has(GradingRule::factory()->count(3))
            ->create(['is_active' => true]);

        $assessment = Assessment::factory()->create([
            'grading_scheme_id' => $gradingScheme->id,
            'total_marks' => 100
        ]);

        $marksData = [
            [
                'student_id' => 1,
                'marks_obtained' => 85,
                'status' => 'present'
            ],
            [
                'student_id' => 2,
                'marks_obtained' => 92,
                'status' => 'present'
            ],
            [
                'student_id' => 3,
                'status' => 'absent'
            ]
        ];

        $results = $this->assessmentService->bulkUpdateMarks($assessment, $marksData);

        $this->assertEquals(3, $results['updated']);
        $this->assertEmpty($results['errors']);
        $this->assertEquals(3, $assessment->studentMarks()->count());
    }

    /** @test */
    public function it_can_get_assessment_analytics()
    {
        $gradingScheme = GradingScheme::factory()
            ->has(GradingRule::factory()->count(3))
            ->create(['is_active' => true]);

        $assessment = Assessment::factory()->create([
            'grading_scheme_id' => $gradingScheme->id,
            'total_marks' => 100
        ]);

        // Create some student marks
        StudentMark::factory()->count(5)->create([
            'assessment_id' => $assessment->id,
            'status' => 'present',
            'marks_obtained' => $this->faker->numberBetween(60, 95)
        ]);

        $analytics = $this->assessmentService->getAssessmentAnalytics($assessment);

        $this->assertArrayHasKey('basic_stats', $analytics);
        $this->assertArrayHasKey('completion_stats', $analytics);
        $this->assertArrayHasKey('grade_distribution', $analytics);
        $this->assertArrayHasKey('performance_insights', $analytics);
        $this->assertArrayHasKey('comparison_data', $analytics);
    }

    /** @test */
    public function it_can_generate_assessment_report()
    {
        $gradingScheme = GradingScheme::factory()
            ->has(GradingRule::factory()->count(3))
            ->create(['is_active' => true]);

        $assessment = Assessment::factory()->create([
            'grading_scheme_id' => $gradingScheme->id
        ]);

        $report = $this->assessmentService->generateAssessmentReport($assessment);

        $this->assertArrayHasKey('assessment', $report);
        $this->assertArrayHasKey('analytics', $report);
        $this->assertArrayHasKey('student_marks', $report);
        $this->assertArrayHasKey('generated_at', $report);
        $this->assertInstanceOf(Assessment::class, $report['assessment']);
    }

    /** @test */
    public function it_can_get_dashboard_data()
    {
        Assessment::factory()->count(10)->create();

        $dashboardData = $this->assessmentService->getDashboardData();

        $this->assertArrayHasKey('total_assessments', $dashboardData);
        $this->assertArrayHasKey('active_assessments', $dashboardData);
        $this->assertArrayHasKey('completed_assessments', $dashboardData);
        $this->assertArrayHasKey('overdue_assessments', $dashboardData);
        $this->assertArrayHasKey('average_completion_rate', $dashboardData);
        $this->assertArrayHasKey('recent_assessments', $dashboardData);
        $this->assertArrayHasKey('assessments_by_type', $dashboardData);
        $this->assertArrayHasKey('assessments_by_status', $dashboardData);
    }

    /** @test */
    public function it_can_filter_dashboard_data()
    {
        Assessment::factory()->create(['type' => 'exam', 'status' => 'active']);
        Assessment::factory()->create(['type' => 'test', 'status' => 'completed']);
        Assessment::factory()->create(['type' => 'quiz', 'status' => 'draft']);

        $filters = [
            'type' => 'exam',
            'status' => 'active'
        ];

        $dashboardData = $this->assessmentService->getDashboardData($filters);

        $this->assertEquals(1, $dashboardData['total_assessments']);
        $this->assertEquals(1, $dashboardData['active_assessments']);
        $this->assertEquals(0, $dashboardData['completed_assessments']);
    }

    /** @test */
    public function it_can_archive_old_assessments()
    {
        // Create old completed assessments
        Assessment::factory()->count(3)->create([
            'status' => 'completed',
            'updated_at' => now()->subDays(400)
        ]);

        // Create recent assessments
        Assessment::factory()->count(2)->create([
            'status' => 'completed',
            'updated_at' => now()->subDays(30)
        ]);

        $archivedCount = $this->assessmentService->archiveOldAssessments(365);

        $this->assertEquals(3, $archivedCount);
        $this->assertEquals(3, Assessment::where('status', 'archived')->count());
        $this->assertEquals(2, Assessment::where('status', 'completed')->count());
    }

    /** @test */
    public function it_can_get_assessment_statistics()
    {
        Assessment::factory()->count(5)->create(['type' => 'exam']);
        Assessment::factory()->count(3)->create(['type' => 'test']);
        Assessment::factory()->count(2)->create(['status' => 'completed']);

        $statistics = $this->assessmentService->getAssessmentStatistics();

        $this->assertEquals(10, $statistics['total_count']);
        $this->assertArrayHasKey('by_type', $statistics);
        $this->assertArrayHasKey('by_status', $statistics);
        $this->assertArrayHasKey('average_total_marks', $statistics);
        $this->assertArrayHasKey('completion_rate', $statistics);
    }

    /** @test */
    public function it_prevents_creating_assessment_with_inactive_grading_scheme()
    {
        $inactiveGradingScheme = GradingScheme::factory()->create(['is_active' => false]);

        $this->expectException(ValidationException::class);

        $assessmentData = [
            'name' => 'Test Assessment',
            'type' => 'test',
            'subject_id' => 1,
            'grade_level_id' => 1,
            'grading_scheme_id' => $inactiveGradingScheme->id,
            'academic_year_id' => 1,
            'total_marks' => 100,
            'weightage' => 20,
            'status' => 'draft',
        ];

        $this->assessmentService->createAssessment($assessmentData);
    }
}
