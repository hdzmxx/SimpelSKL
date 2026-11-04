<?php

namespace Tests\Unit;

use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Services\Grading;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use PHPUnit\Framework\TestCase;

class GradingTest extends TestCase
{
    public function test_average_includes_missing_values_in_the_divisor(): void
    {
        $this->assertEquals(60.0, Grading::averageIncludingMissing([80, null, 100]));
    }

    public function test_rapor_average_uses_all_five_semesters_when_some_are_missing(): void
    {
        $this->assertEquals(20.0, Grading::averageIncludingMissing([100, null, null, null, null]));
    }

    public function test_semester_average_includes_unfilled_subjects_as_zero(): void
    {
        $filledSubject = new Subject();
        $filledSubject->id = 1;
        $emptySubject = new Subject();
        $emptySubject->id = 2;

        $student = new Student(['no_urut' => 1, 'kelas' => 'IX A']);
        $student->setRelation('scores', new EloquentCollection([
            new Score(['subject_id' => 1, 'sem1' => 80]),
        ]));

        Grading::applySemester(collect([$student]), collect([$filledSubject, $emptySubject]), 1);

        $this->assertEquals(40.0, $student->avg);
        $this->assertEquals(80.0, $student->jumlah);
        $this->assertNull($student->fin[2]);
    }

    public function test_final_subject_grade_uses_all_six_semesters_and_exam_weight(): void
    {
        $subject = new Subject([
            'bobot_semester' => 60,
            'bobot_tertulis' => 40,
            'bobot_praktik' => 0,
            'ada_praktik' => false,
            'ujian_dari_sem6' => false,
        ]);
        $score = new Score(['sem1' => 80, 'tertulis' => 90]);

        $this->assertEquals(44.0, Grading::detail($subject, $score)['akhir']);
    }
}
