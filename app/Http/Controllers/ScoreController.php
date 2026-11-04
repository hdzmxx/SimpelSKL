<?php

namespace App\Http\Controllers;

use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Services\ScoreImporter;
use App\Services\Sheets;
use Illuminate\Http\Request;

/** Input, ekspor, dan impor nilai per mapel. */
class ScoreController extends Controller
{
    /** Tabel input satu mapel untuk satu kelas. */
    public function grid(Request $r, Subject $subject)
    {
        if (!$subject->aktif) {
            return redirect()->route('mapel.index')->withErrors(['mapel' => "Mapel {$subject->kode} nonaktif. Aktifkan dulu di Mapel & Bobot."]);
        }
        $kelasList = Student::kelasList();
        $kelas = $r->query('kelas', $kelasList->first());
        $students = Student::where('kelas', $kelas)->orderBy('no_urut')->get();
        $scores = Score::where('subject_id', $subject->id)->whereIn('student_id', $students->pluck('id'))->get()->keyBy('student_id');
        $fields = $subject->fields();
        $filled = 0;
        foreach ($students as $student) {
            $score = $scores->get($student->id);
            foreach ($fields as $field) {
                if ($score?->$field !== null) $filled++;
            }
        }

        return view('scores.grid', [
            'subject' => $subject, 'subjects' => Subject::aktif()->orderBy('urut')->get(),
            'kelasList' => $kelasList, 'kelas' => $kelas, 'students' => $students, 'scores' => $scores,
            'filled' => $filled, 'totalFields' => $students->count() * count($fields),
        ]);
    }

    public function save(Request $r, Subject $subject)
    {
        $kelas = (string) $r->query('kelas');
        $r->validate(['scores' => 'required|array', 'scores.*.*' => 'nullable|numeric|between:0,100']);

        $allowed = Student::where('kelas', $kelas)->pluck('id')->all();
        $fields = $subject->fields();
        $n = 0;
        foreach ($r->input('scores') as $sid => $vals) {
            if (!in_array((int) $sid, $allowed, true)) continue;
            $attrs = [];
            foreach ($fields as $f) {
                if (array_key_exists($f, $vals)) {
                    $attrs[$f] = ($vals[$f] === null || $vals[$f] === '') ? null : (float) $vals[$f];
                }
            }
            Score::updateOrCreate(['student_id' => (int) $sid, 'subject_id' => $subject->id], $attrs);
            $n++;
        }
        return redirect()->route('nilai.grid', ['subject' => $subject, 'kelas' => $kelas])->with('ok', "Nilai $subject->kode untuk $n siswa tersimpan.");
    }

    /** Ekspor satu mapel: No, NISN, Nama, Kelas, S1..S6, Tertulis, Praktik. Sekaligus template impor.
     *  ?blank=1 → kolom nilai dikosongkan (format kosongan berisi nama siswa). */
    public function export(Request $r, Subject $subject)
    {
        $kelas = $r->query('kelas');
        $blank = $r->boolean('blank');
        $students = Student::when($kelas, fn ($q) => $q->where('kelas', $kelas))->orderBy('no_urut')->get();
        $scores = $blank ? collect() : Score::where('subject_id', $subject->id)->get()->keyBy('student_id');
        $fields = $subject->fields();

        $head = ['No', 'NISN', 'Nama', 'Kelas'];
        foreach ($fields as $f) {
            $head[] = match ($f) { 'tertulis' => 'Tertulis', 'praktik' => 'Praktik', default => 'S' . substr($f, 3) };
        }
        $rows = [$head];
        foreach ($students as $s) {
            $sc = $scores->get($s->id);
            $row = [$s->no_urut, $s->nisn, $s->nama, $s->kelas];
            foreach ($fields as $f) $row[] = $blank ? null : $sc?->$f;
            $rows[] = $row;
        }
        $base = ($blank ? 'template-' : 'nilai-') . strtolower($subject->kode) . ($kelas ? '-' . strtolower(str_replace(' ', '', $kelas)) : '');
        return Sheets::download([$subject->kode => $rows], $base, $r->query('format') === 'csv' ? 'csv' : 'xlsx', [1, 2, 3]);
    }

    /** Unggah file nilai satu mapel -> pratinjau. */
    public function import(Request $r, Subject $subject)
    {
        $r->validate(['file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120']);
        $result = ScoreImporter::parse(Sheets::read($r->file('file')), Subject::aktif()->orderBy('urut')->get(), $subject);
        session(['import' => ['name' => $r->file('file')->getClientOriginalName(), 'back' => route('nilai.grid', $subject), 'result' => $result]]);
        return redirect()->route('impor.pratinjau');
    }
}
