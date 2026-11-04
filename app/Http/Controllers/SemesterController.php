<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Services\Grading;
use App\Services\ScoreImporter;
use App\Services\Sheets;
use App\Support\KolomCetak;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/** Nilai rapor per semester: satu semester, semua mapel dalam kolom (seperti sheet NILAI SEM di Excel). */
class SemesterController extends Controller
{
    private function table($students, $subjects, int $n): array
    {
        Grading::applySemester($students, $subjects, $n);
        $rows = [array_merge(['No', 'NISN', 'Nama', 'Kelas'], $subjects->pluck('kode')->all(), ['Jumlah', 'Rata-rata'])];
        foreach ($students as $s) {
            $row = [$s->no_urut, $s->nisn, $s->nama, $s->kelas];
            foreach ($subjects as $m) $row[] = $s->fin[$m->id];
            $row[] = round($s->jumlah, 2);
            $row[] = round($s->avg, 2);
            $rows[] = $row;
        }
        return $rows;
    }

    /** Unggah file nilai satu semester (kolom = mapel) atau file semua semester -> pratinjau. */
    public function import(Request $r)
    {
        $r->validate(['file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240', 'sem' => 'required|integer|between:1,6']);
        $n = (int) $r->input('sem');
        $result = ScoreImporter::parseSemester(Sheets::read($r->file('file')), Subject::aktif()->orderBy('urut')->get(), $n);
        session(['import' => ['name' => $r->file('file')->getClientOriginalName(), 'back' => route('semester', ['sem' => $n]), 'result' => $result]]);
        return redirect()->route('impor.pratinjau');
    }

    /**
     * Mapel (aktif) dalam urutan kolom untuk CETAK semester $n. Urutan ini hanya dipakai di cetak nilai semester;
     * SKL, rapor, transkrip, dan tabel lain tetap memakai urutan mapel. Mapel yang belum ada di urutan tersimpan
     * (mis. mapel baru) ditaruh di belakang menurut urutan mapel.
     */
    private function urutanCetak(Collection $subjects, int $n): Collection
    {
        return KolomCetak::urutan($subjects, $n);
    }

    /** Simpan urutan kolom cetak untuk satu semester (kosong = kembali ke urutan mapel). */
    public function simpanUrutan(Request $r)
    {
        $d = $r->validate(['sem' => 'required|integer|between:1,6', 'urut' => 'nullable|array', 'urut.*' => 'string|max:20']);
        $valid = Subject::aktif()->pluck('kode')->all();
        $urut = array_values(array_unique(array_filter($d['urut'] ?? [], fn ($k) => in_array($k, $valid, true))));
        Setting::put(['urut_cetak_' . $d['sem'] => $urut ? json_encode($urut) : '']);
        return response()->json(['ok' => true, 'jumlah' => count($urut)]);
    }

    /**
     * Cetak nilai semester: satu lembar per kelas per semester, tanpa kop dan tanda tangan.
     * Pilih semester (1-6 atau semua) dan satu/banyak kelas. Ukuran kertas dan orientasi diatur di halaman cetak.
     */
    public function cetak(Request $r)
    {
        $r->validate([
            'sem' => ['required', 'regex:/^([1-6]|semua)$/'],
            'kelas' => 'nullable|array', 'kelas.*' => 'string|max:20',
            'kertas' => 'nullable|in:a4,f4,letter,legal',
            'orientasi' => 'nullable|in:auto,landscape,portrait',
            'urut' => 'nullable|in:kelas,semester',
        ]);
        $sems = $r->query('sem') === 'semua' ? range(1, 6) : [(int) $r->query('sem')];
        $subjects = Subject::aktif()->orderBy('urut')->get();
        $orders = [];
        foreach ($sems as $n) $orders[$n] = $this->urutanCetak($subjects, $n);

        $semua = Student::kelasList();
        $pilih = (array) $r->query('kelas', []);
        $kelas = $semua->filter(fn ($k) => in_array($k, $pilih, true))->values();
        if ($kelas->isEmpty()) $kelas = $semua;

        $byKelas = Student::with('scores')->whereIn('kelas', $kelas)->orderBy('no_urut')->get()->groupBy('kelas');

        // Urutan lembar: per kelas (Sem 1-6 berurutan) atau per semester (semua kelas)
        $pairs = [];
        if ($r->query('urut') === 'semester') {
            foreach ($sems as $n) foreach ($kelas as $k) $pairs[] = [$k, $n];
        } else {
            foreach ($kelas as $k) foreach ($sems as $n) $pairs[] = [$k, $n];
        }

        $sheets = [];
        foreach ($pairs as [$k, $n]) {
            $list = $byKelas->get($k);
            if (!$list || $list->isEmpty()) continue;
            $ord = $orders[$n];
            Grading::applySemester($list, $ord, $n);
            $rows = [];
            foreach ($list as $i => $s) {
                $rows[] = ['no' => $i + 1, 'nama' => $s->nama, 'v' => $ord->map(fn ($m) => $s->fin[$m->id])->all(), 'jumlah' => $s->jumlah, 'avg' => $s->avg];
            }
            $rata = [];
            foreach ($ord as $m) {
                $rata[] = Grading::averageIncludingMissing($list->map(fn ($s) => $s->fin[$m->id])->all());
            }
            $sheets[] = ['kelas' => $k, 'sem' => $n, 'rows' => $rows, 'rata' => $rata, 'subjects' => $ord];
        }

        return view('semester.cetak', [
            'sheets' => $sheets, 'subjects' => $subjects, 'orders' => $orders, 'sems' => $sems,
            'kertas' => $r->query('kertas', 'f4'), 'orientasi' => $r->query('orientasi', 'landscape'),
        ]);
    }

    public function index(Request $r)
    {
        $n = (int) $r->query('sem', 1);
        if ($n < 1 || $n > 6) $n = 1;
        $kelas = $r->query('kelas');
        $subjects = Subject::aktif()->orderBy('urut')->get();
        $students = Student::with('scores')->when($kelas, fn ($q) => $q->where('kelas', $kelas))->orderBy('no_urut')->get();

        if ($r->query('format')) {
            $csv = $r->query('format') === 'csv';
            $base = 'nilai-semester' . ($kelas ? '-' . strtolower(str_replace(' ', '', $kelas)) : '');
            if ($r->boolean('semua') && !$csv) {
                $sheets = [];
                for ($i = 1; $i <= 6; $i++) $sheets["Sem $i"] = $this->table($students, $subjects, $i);
                return Sheets::download($sheets, $base . '-semua', 'xlsx', [1, 2, 3]);
            }
            return Sheets::download(["Sem $n" => $this->table($students, $subjects, $n)], "$base-$n", $csv ? 'csv' : 'xlsx', [1, 2, 3]);
        }

        Grading::applySemester($students, $subjects, $n);
        // Nilai kosong dihitung 0 dan tetap masuk pembagi rata-rata kelas.
        $rataMapel = [];
        foreach ($subjects as $m) {
            $rataMapel[$m->id] = Grading::averageIncludingMissing($students->map(fn ($s) => $s->fin[$m->id])->all());
        }
        return view('semester.index', [
            'n' => $n, 'kelas' => $kelas, 'kelasList' => Student::kelasList(),
            'subjects' => $subjects, 'students' => $students, 'rataMapel' => $rataMapel,
        ]);
    }
}
