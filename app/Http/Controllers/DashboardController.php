<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Subject;
use App\Services\Grading;
use App\Services\Kelulusan;

class DashboardController extends Controller
{
    public function index()
    {
        $subjects = Subject::aktif()->orderBy('urut')->get();
        $students = Student::with('scores')->orderBy('kelas')->orderBy('no_urut')->get();

        Grading::apply($students, $subjects);

        $totalRequired = 0;
        $totalFilled = 0;
        $stats = $subjects->map(function ($m) use ($students) {
            $req = 0;
            $fill = 0;
            $scores = [];
            foreach ($students as $st) {
                $sc = $st->scores->firstWhere('subject_id', $m->id);
                $scores[] = $st->fin[$m->id];
                foreach ($m->fields() as $f) {
                    $req++;
                    if ($sc && $sc->$f !== null) $fill++;
                }
            }
            return [
                'm' => $m,
                'req' => $req,
                'fill' => $fill,
                'missing' => $req - $fill,
                'average' => $scores ? array_sum($scores) / count($scores) : null,
            ];
        });
        $totalRequired = $stats->sum('req');
        $totalFilled = $stats->sum('fill');
        $averageSchool = $students->isNotEmpty() && $subjects->isNotEmpty()
            ? $students->avg('avg')
            : null;
        $topRanked = Grading::rankAll($students)->take(10);

        $gender = [
            'L' => $students->filter(fn ($st) => strtoupper((string) $st->jenis_kelamin) === 'L')->count(),
            'P' => $students->filter(fn ($st) => strtoupper((string) $st->jenis_kelamin) === 'P')->count(),
            'unknown' => $students->filter(fn ($st) => !in_array(strtoupper((string) $st->jenis_kelamin), ['L', 'P'], true))->count(),
        ];
        $perKelas = $students->groupBy('kelas')->sortKeys()->map(function ($list) {
            return [
                'siswa' => $list->count(),
                'laki' => $list->filter(fn ($st) => strtoupper((string) $st->jenis_kelamin) === 'L')->count(),
                'perempuan' => $list->filter(fn ($st) => strtoupper((string) $st->jenis_kelamin) === 'P')->count(),
                'alpa' => $list->sum(fn ($st) => (int) (($st->absensi[2] ?? 0) + ($st->absensi[5] ?? 0))),
            ];
        });

        $aturan = Kelulusan::aturan();
        $kesiapan = ['ok' => 0, 'belum' => 0, 'tidak' => 0];
        $penetapan = ['lulus' => 0, 'tidak' => 0, 'belum' => 0];
        foreach ($students as $student) {
            $evaluasi = Kelulusan::evaluasi($student, $subjects, $aturan);
            $kesiapan[$evaluasi['status']]++;
            $penetapan[$student->status_lulus === 'lulus' ? 'lulus' : ($student->status_lulus === 'tidak' ? 'tidak' : 'belum')]++;
        }

        return view('dashboard', [
            'jumlahSiswa' => $students->count(),
            'perKelas' => $perKelas,
            'stats' => $stats,
            'gender' => $gender,
            'totalRequired' => $totalRequired,
            'totalFilled' => $totalFilled,
            'averageSchool' => $averageSchool,
            'topRanked' => $topRanked,
            'overallPct' => $totalRequired ? round(100 * $totalFilled / $totalRequired) : 0,
            'kesiapan' => $kesiapan,
            'penetapan' => $penetapan,
        ]);
    }
}
