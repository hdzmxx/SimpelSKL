<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Setting;
use App\Models\Subject;
use App\Services\Grading;
use App\Services\Sheets;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private function data(?string $kelas): array
    {
        $subjects = Subject::aktif()->orderBy('urut')->get();
        $students = Student::with('scores')->when($kelas, fn ($q) => $q->where('kelas', $kelas))->orderBy('no_urut')->get();
        Grading::apply($students, $subjects);
        return [$subjects, $students];
    }

    private function table(\Illuminate\Support\Collection $students, \Illuminate\Support\Collection $subjects, bool $rank = false): array
    {
        $head = array_merge($rank ? ['Peringkat'] : ['No'], ['NISN', 'Nama', 'Kelas'], $subjects->pluck('kode')->all(), ['Rata-rata']);
        $rows = [$head];
        foreach ($students as $i => $s) {
            $row = [$rank ? $i + 1 : $s->no_urut, $s->nisn, $s->nama, $s->kelas];
            foreach ($subjects as $m) $row[] = round($s->fin[$m->id], 2);
            $row[] = round($s->avg, 2);
            $rows[] = $row;
        }
        return $rows;
    }

    /** Master nilai: rincian pembobotan satu mapel. */
    public function master(Request $r)
    {
        $kelas = $r->query('kelas');
        [$subjects, $students] = $this->data($kelas);
        $subject = $subjects->firstWhere('id', (int) $r->query('mapel')) ?? $subjects->first();
        $detail = $subject ? $students->mapWithKeys(fn ($s) => [$s->id => \App\Services\Grading::detail($subject, $s->scores->firstWhere('subject_id', $subject->id))]) : collect();

        return view('reports.master', ['subjects' => $subjects, 'subject' => $subject, 'students' => $students, 'detail' => $detail, 'kelas' => $kelas, 'kelasList' => Student::kelasList()]);
    }

    public function akhir(Request $r)
    {
        $kelas = $r->query('kelas');
        [$subjects, $students] = $this->data($kelas);
        if ($r->query('format')) {
            return Sheets::download(['Nilai Akhir' => $this->table($students, $subjects)], 'nilai-akhir', $r->query('format') === 'csv' ? 'csv' : 'xlsx', [1, 2, 3]);
        }
        $kosong = $students->mapWithKeys(fn ($s) => [$s->id => Grading::missing($s, $subjects)[0]]);
        return view('reports.akhir', ['subjects' => $subjects, 'students' => $students, 'kosong' => $kosong, 'kelas' => $kelas, 'kelasList' => Student::kelasList()]);
    }

    /**
     * Peringkat paralel (seluruh siswa) atau per kelas, berdasarkan nilai akhir atau rata-rata satu semester.
     * Peringkat paralel dan peringkat kelas selalu dihitung dari seluruh siswa tahun pelajaran ini;
     * filter kelas hanya membatasi tampilan.
     */
    public function peringkat(Request $r)
    {
        $kelas = $r->query('kelas');
        $cetak = $r->boolean('cetak');
        $top = $cetak ? 'all' : $r->query('top', '10');
        $tampil = $r->query('tampil') === 'kelas' ? 'kelas' : 'paralel';
        $dasar = (string) $r->query('dasar', 'akhir');
        $subjects = Subject::aktif()->orderBy('urut')->get();
        $all = Student::with('scores')->orderBy('no_urut')->get();
        if (preg_match('/^[1-6]$/', $dasar)) {
            Grading::applySemester($all, $subjects, (int) $dasar);
        } else {
            $dasar = 'akhir';
            Grading::apply($all, $subjects);
        }
        $ranked = Grading::rankAll($all);
        if ($kelas) $ranked = $ranked->where('kelas', $kelas)->values();

        if ($tampil === 'kelas') {
            $groups = $ranked->groupBy('kelas')->sortKeys()->map(fn ($g) => $g->sortBy('r_kelas')->values());
        } else {
            $groups = collect(['Paralel' => $ranked]);
        }
        if ($top !== 'all') $groups = $groups->map(fn ($g) => $g->take((int) $top));

        if ($cetak) {
            return view('reports.peringkat-cetak', [
                'subjects' => $subjects,
                'groups' => $groups,
                'tampil' => $tampil,
                'dasar' => $dasar,
                'tahun' => \App\Support\Year::current()?->nama,
                'settings' => Setting::map(),
            ]);
        }

        if ($r->query('format')) {
            $sheets = [];
            foreach ($groups as $judul => $list) {
                $head = array_merge(['Peringkat Paralel', 'Peringkat Kelas', 'NISN', 'Nama', 'Kelas'], $subjects->pluck('kode')->all(), ['Rata-rata']);
                $rows = [$head];
                foreach ($list as $s) {
                    $row = [$s->r_paralel, $s->r_kelas, $s->nisn, $s->nama, $s->kelas];
                    foreach ($subjects as $m) $row[] = $s->fin[$m->id] === null ? null : round($s->fin[$m->id], 2);
                    $row[] = round($s->avg, 2);
                    $rows[] = $row;
                }
                $sheets[(string) $judul] = $rows;
            }
            return Sheets::download($sheets, 'peringkat-' . $tampil . '-' . $dasar, $r->query('format') === 'csv' ? 'csv' : 'xlsx', [2]);
        }
        return view('reports.peringkat', [
            'subjects' => $subjects, 'groups' => $groups, 'kelas' => $kelas, 'top' => $top,
            'tampil' => $tampil, 'dasar' => $dasar, 'kelasList' => Student::kelasList(),
        ]);
    }
}
