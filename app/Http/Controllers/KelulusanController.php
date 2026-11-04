<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Services\Grading;
use App\Services\Kelulusan;
use App\Services\Sheets;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KelulusanController extends Controller
{
    /** Semua siswa tahun terpilih + hasil evaluasi. */
    private function muat(): array
    {
        $subjects = Subject::aktif()->orderBy('urut')->get();
        $students = Student::with('scores')->orderBy('no_urut')->get();
        Grading::apply($students, $subjects);
        $a = Kelulusan::aturan();
        foreach ($students as $st) {
            $absensi = $st->absensi ?? [];
            $st->setAttribute('alpa', (int) ($absensi[2] ?? 0) + (int) ($absensi[5] ?? 0));
            $st->setAttribute('evaluasi', Kelulusan::evaluasi($st, $subjects, $a));
        }
        return [$students, $a];
    }

    private function saring($students, ?string $kelas, ?string $status)
    {
        return $students->filter(function ($s) use ($kelas, $status) {
            if ($kelas && $s->kelas !== $kelas) return false;
            return match ($status) {
                'ok', 'belum', 'tidak' => $s->evaluasi['status'] === $status,
                'tetap_lulus' => $s->status_lulus === 'lulus',
                'tetap_tidak' => $s->status_lulus === 'tidak',
                'belum_tetap' => !$s->status_lulus,
                default => true,
            };
        })->values();
    }

    public function index(Request $r)
    {
        [$students, $a] = $this->muat();
        $n = [
            'ok' => $students->filter(fn ($s) => $s->evaluasi['status'] === 'ok')->count(),
            'belum' => $students->filter(fn ($s) => $s->evaluasi['status'] === 'belum')->count(),
            'tidak' => $students->filter(fn ($s) => $s->evaluasi['status'] === 'tidak')->count(),
            'tetap_lulus' => $students->where('status_lulus', 'lulus')->count(),
            'tetap_tidak' => $students->where('status_lulus', 'tidak')->count(),
        ];
        return view('kelulusan.index', [
            'list' => $this->saring($students, $r->query('kelas'), $r->query('status')),
            'a' => $a, 'n' => $n, 'total' => $students->count(),
            'kelas' => $r->query('kelas'), 'status' => $r->query('status'), 'kelasList' => Student::kelasList(),
        ]);
    }

    public function aturan(Request $r)
    {
        $r->validate([
            'lulus_min_mapel' => 'nullable|numeric|between:0,100', 'lulus_min_rata' => 'nullable|numeric|between:0,100',
            'lulus_sikap' => ['nullable', Rule::in(array_keys(Kelulusan::SIKAP))], 'lulus_maks_alpa' => 'nullable|integer|min:0',
        ]);
        Setting::put([
            'lulus_lengkap' => $r->boolean('lulus_lengkap') ? '1' : '0',
            'lulus_min_mapel' => $r->input('lulus_min_mapel'), 'lulus_min_rata' => $r->input('lulus_min_rata'),
            'lulus_sikap' => $r->input('lulus_sikap'), 'lulus_maks_alpa' => $r->input('lulus_maks_alpa'),
        ]);
        return back()->with('ok', 'Aturan kelulusan disimpan.');
    }

    public function sikap(Request $r)
    {
        $r->validate(['sikap' => 'array', 'sikap.*' => ['nullable', Rule::in(array_keys(Kelulusan::SIKAP))]]);
        $n = 0;
        foreach ((array) $r->input('sikap', []) as $id => $v) {
            $n += Student::whereKey((int) $id)->update(['sikap' => $v ?: null]);
        }
        return back()->with('ok', "Sikap disimpan ($n siswa).");
    }

    /** Terapkan satu nilai sikap sekaligus: ke semua siswa yang tampil (sesuai filter kelas/status) atau ke yang dicentang. */
    public function sikapMassal(Request $r)
    {
        $d = $r->validate([
            'sikap_massal' => ['required', Rule::in(array_merge(array_keys(Kelulusan::SIKAP), ['-']))],
            'cakupan' => 'required|in:semua,pilih', 'pilih' => 'array', 'pilih.*' => 'integer',
            'kelas' => 'nullable|string|max:20', 'status' => 'nullable|string|max:20',
        ]);
        if ($d['cakupan'] === 'pilih') {
            $ids = (array) ($d['pilih'] ?? []);
            if (!$ids) return back()->withErrors(['pilih' => 'Centang minimal satu siswa.']);
        } else {
            [$students] = $this->muat();
            $ids = $this->saring($students, $d['kelas'] ?? null, $d['status'] ?? null)->pluck('id')->all();
        }
        $q = Student::whereIn('id', $ids);
        if ($r->boolean('hanya_kosong')) $q->where(fn ($x) => $x->whereNull('sikap')->orWhere('sikap', ''));
        $hapus = $d['sikap_massal'] === '-';
        $n = $q->update(['sikap' => $hapus ? null : $d['sikap_massal']]);
        return back()->with('ok', $hapus ? "Sikap dikosongkan ($n siswa)." : "Sikap \"{$d['sikap_massal']}\" diterapkan ke $n siswa.");
    }

    /** Penetapan hasil rapat dewan guru: lulus / tidak lulus / lepas (kembali mengikuti hitungan). */
    public function tetapkan(Request $r)
    {
        $d = $r->validate(['pilih' => 'required|array|min:1', 'pilih.*' => 'integer', 'aksi' => 'required|in:lulus,tidak,lepas', 'catatan' => 'nullable|string|max:255']);
        $lepas = $d['aksi'] === 'lepas';
        $n = Student::whereIn('id', $d['pilih'])->update([
            'status_lulus' => $lepas ? null : $d['aksi'],
            'catatan_lulus' => $lepas ? null : ($d['catatan'] ?? null),
        ]);
        return back()->with('ok', $lepas ? "Penetapan dilepas ($n siswa)." : "$n siswa ditetapkan " . ($d['aksi'] === 'lulus' ? 'LULUS' : 'TIDAK LULUS') . '.');
    }

    public function ekspor(Request $r)
    {
        [$students] = $this->muat();
        $rows = [['No', 'NISN', 'Nama', 'Kelas', 'Rata-rata', 'Sikap', 'Hasil hitungan', 'Alpa (Sem 5 + Sem 6)', 'Alasan', 'Penetapan', 'Catatan']];
        $label = ['ok' => 'Memenuhi', 'belum' => 'Data belum lengkap', 'tidak' => 'Belum memenuhi'];
        foreach ($this->saring($students, $r->query('kelas'), $r->query('status')) as $s) {
            $rows[] = [$s->no_urut, $s->nisn, $s->nama, $s->kelas, round($s->avg, 2), $s->sikap, $label[$s->evaluasi['status']],
                $s->alpa, implode('; ', $s->evaluasi['alasan']), $s->status_lulus === 'lulus' ? 'Lulus' : ($s->status_lulus === 'tidak' ? 'Tidak lulus' : ''), $s->catatan_lulus];
        }
        return Sheets::download(['Kelulusan' => $rows], 'kelulusan', $r->query('format') === 'csv' ? 'csv' : 'xlsx', [1, 2, 3]);
    }
}
