<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Services\Grading;
use App\Services\Kelulusan;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/** SKL, Transkrip Nilai, dan Surat Keterangan Nilai Rapor (dicetak lewat browser / Simpan PDF). */
class DocumentController extends Controller
{
    public function index()
    {
        return view('documents.index', [
            'subjects' => Subject::aktif()->orderBy('urut')->get(), 'kelasList' => Student::kelasList(),
            'students' => Student::orderBy('no_urut')->get(['id', 'no_urut', 'nisn', 'nis', 'nama', 'kelas']),
            's' => Setting::map(), 'groups' => SettingController::DOKUMEN,
        ]);
    }

    public function skKelulusan()
    {
        $students = Student::where('status_lulus', 'lulus')->orderBy('kelas')->orderBy('no_urut')->get(['id', 'no_urut', 'nisn', 'nis', 'nama', 'kelas', 'jenis_kelamin']);
        return view('documents.sk-kelulusan-index', [
            'students' => $students,
            'total' => $students->count(),
            'laki' => $students->where('jenis_kelamin', 'L')->count(),
            'perempuan' => $students->where('jenis_kelamin', 'P')->count(),
            's' => Setting::map(),
            'legalDefaults' => SettingController::SK_LEGAL_DEFAULTS,
            'groups' => SettingController::DOKUMEN,
        ]);
    }

    public function cetakSkKelulusan(Request $request)
    {
        $request->validate(['lampiran' => 'required|in:nilai,keterangan', 'format_nama' => 'nullable|in:title,upper']);
        $lampiran = $request->query('lampiran');
        $subjects = $lampiran === 'nilai' ? Subject::aktif()->orderBy('urut')->get() : collect();
        $query = Student::query()
            ->where('status_lulus', 'lulus')
            ->orderBy('kelas')
            ->orderBy('no_urut');
        if ($lampiran === 'nilai') $query->with('scores');
        $students = $query->get();
        $formatNama = $this->formatNama($request->query('format_nama', 'title'));
        $this->applyFormatNama($students, $formatNama);
        if ($lampiran === 'nilai') Grading::apply($students, $subjects);

        $statistik = [];
        if ($lampiran === 'nilai') {
            $aturan = Kelulusan::aturan();
            foreach ($students as $student) {
                $values = array_values($student->fin);
                $absensi = $student->absensi ?? [];
                $student->setAttribute('jumlah_nilai', array_sum($values));
                $student->setAttribute('alpa', (int) ($absensi[2] ?? 0) + (int) ($absensi[5] ?? 0));
                $student->setAttribute('kehadiran', $aturan['maks_alpa'] === null || $student->alpa <= $aturan['maks_alpa'] ? 'Memenuhi' : 'Tidak memenuhi');
            }
            foreach ($subjects as $subject) {
                $values = $students->map(fn ($student) => (float) ($student->fin[$subject->id] ?? 0))->all();
                $statistik[$subject->id] = $this->statistikNilai($values);
            }
            $statistik['jumlah'] = $this->statistikNilai($students->pluck('jumlah_nilai')->map(fn ($value) => (float) $value)->all());
            $statistik['rata_rata'] = $this->statistikNilai($students->pluck('avg')->map(fn ($value) => (float) $value)->all());
        }
        $s = Setting::map();

        return view('documents.sk-kelulusan', [
            'students' => $students,
            'subjects' => $subjects,
            'statistik' => $statistik,
            'lampiran' => $lampiran,
            'formatNama' => $formatNama,
            's' => $s,
            'legalDefaults' => SettingController::SK_LEGAL_DEFAULTS,
            'laki' => $students->where('jenis_kelamin', 'L')->count(),
            'perempuan' => $students->where('jenis_kelamin', 'P')->count(),
        ]);
    }

    private function statistikNilai(array $values): array
    {
        return [
            'rata_rata' => $values ? array_sum($values) / count($values) : 0,
            'terendah' => $values ? min($values) : 0,
            'tertinggi' => $values ? max($values) : 0,
        ];
    }

    private function formatNama(string $format): string
    {
        return in_array($format, ['title', 'upper'], true) ? $format : 'title';
    }

    private function applyFormatNama(Collection $students, string $format): void
    {
        foreach ($students as $student) {
            $name = trim((string) $student->nama);
            $student->setAttribute(
                'nama_cetak',
                $format === 'upper'
                    ? mb_strtoupper($name, 'UTF-8')
                    : mb_convert_case(mb_strtolower($name, 'UTF-8'), MB_CASE_TITLE, 'UTF-8')
            );
        }
    }

    /** Simpan pengaturan kelulusan, tanda tangan, dan penomoran untuk tahun pelajaran yang dipilih. */
    public function simpanPengaturan(Request $r)
    {
        $keys = array_merge(...array_map('array_keys', array_values(SettingController::DOKUMEN)));
        $validation = array_fill_keys($keys, 'nullable|string|max:500');
        foreach (array_keys(SettingController::SK_LEGAL_DEFAULTS) as $key) {
            $validation[$key] = 'nullable|string|max:10000';
        }
        $r->validate($validation);
        Setting::put($r->only($keys) + array_fill_keys($keys, ''));
        return redirect(route('sk-kelulusan') . '#pengaturan-dokumen')->with('ok', 'Pengaturan dokumen disimpan untuk tahun pelajaran ' . (\App\Support\Year::current()?->nama ?? '') . '.');
    }

    /** Siswa yang dipilih lewat ?siswa[]=… atau filter kelas / rentang nomor. */
    private function querySiswa(Request $r, bool $skor = true)
    {
        $q = Student::orderBy('no_urut');
        if ($skor) $q->with('scores');
        if ($r->filled('siswa')) {
            $q->whereIn('id', (array) $r->query('siswa'));
        } else {
            $q->when($r->query('kelas'), fn ($x, $k) => $x->where('kelas', $k))
              ->when($r->query('awal'), fn ($x, $a) => $x->where('no_urut', '>=', (int) $a))
              ->when($r->query('akhir'), fn ($x, $a) => $x->where('no_urut', '<=', (int) $a));
        }
        return $q;
    }

    /** Dipakai tombol cetak SKL: apakah semua siswa yang dipilih sudah ditetapkan lulus / tidak lulus? */
    public function cekSkl(Request $r)
    {
        $students = $this->querySiswa($r, false)->get(['id', 'nama', 'status_lulus']);
        $siap = $students->filter(fn ($st) => in_array($st->status_lulus, ['lulus', 'tidak'], true));
        return response()->json([
            'total' => $students->count(),
            'siap' => $siap->count(),
            'belum' => $students->diff($siap)->pluck('nama')->values()->all(),
        ]);
    }

    public function show(Request $r, string $jenis)
    {
        $subjects = Subject::aktif()->orderBy('urut')->get();
        $q = $this->querySiswa($r);
        $students = $q->get();
        $formatNama = $this->formatNama((string) $r->query('format_nama', 'title'));
        $this->applyFormatNama($students, $formatNama);
        Grading::apply($students, $subjects);

        // Mapel pada surat nilai rapor: pilihan saat cetak, bila kosong memakai tanda "tampil di rapor"
        $pilih = $r->query('mapel');
        $raporSubjects = $pilih ? $subjects->whereIn('id', array_map('intval', (array) $pilih))->values() : $subjects->where('tampil_di_rapor', true)->values();

        // SKL hanya untuk siswa yang sudah ditetapkan lulus / tidak lulus (menu Nilai -> Kelulusan).
        // Yang belum ditetapkan tidak dicetak; yang ditetapkan tidak lulus tercetak "TIDAK LULUS" dan diingatkan.
        $blokir = [];
        $peringatan = [];
        if ($jenis === 'skl') {
            $blokir = $students->filter(fn ($st) => !in_array($st->status_lulus, ['lulus', 'tidak'], true))->map(fn ($st) => $st->nama)->values()->all();
            $students = $students->filter(fn ($st) => in_array($st->status_lulus, ['lulus', 'tidak'], true))->values();
            $peringatan = $students->filter(fn ($st) => $st->status_lulus === 'tidak')->map(fn ($st) => $st->nama)->values()->all();
        }

        return view("documents.$jenis", ['s' => Setting::map(), 'students' => $students, 'subjects' => $subjects, 'raporSubjects' => $raporSubjects, 'peringatan' => $peringatan, 'blokir' => $blokir, 'formatNama' => $formatNama, 'boldNama' => $r->boolean('bold_nama')]);
    }
}
