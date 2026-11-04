<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Support\Year;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Tahun pelajaran: tiap tahun punya siswa, mapel + bobot, dan pengaturan kelulusan sendiri. */
class YearController extends Controller
{
    public function index()
    {
        return view('years.index', ['years' => AcademicYear::orderByDesc('nama')->get(), 'cur' => Year::current()]);
    }

    /** Pilih tahun pelajaran yang sedang dikerjakan (berlaku untuk sesi ini). */
    public function select(Request $r)
    {
        $y = AcademicYear::findOrFail((int) $r->input('id'));
        session(['tahun_id' => $y->id]);
        return redirect()->back();
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'nama' => ['required', 'regex:/^\d{4}\/\d{4}$/', 'unique:academic_years,nama'],
            'salin_dari' => 'nullable|exists:academic_years,id',
        ], ['nama.regex' => 'Format tahun pelajaran: 2026/2027.', 'nama.unique' => 'Tahun pelajaran itu sudah ada.']);

        $new = DB::transaction(function () use ($d) {
            $new = AcademicYear::create(['nama' => $d['nama'], 'aktif' => false]);
            if (!empty($d['salin_dari'])) {
                $src = AcademicYear::findOrFail($d['salin_dari']);
                // Mapel + bobot disalin; sesudah itu tiap tahun berdiri sendiri.
                foreach (Subject::withoutGlobalScopes()->where('academic_year_id', $src->id)->orderBy('urut')->get() as $m) {
                    $c = $m->replicate();
                    $c->academic_year_id = $new->id;
                    $c->ujian_dari_sem6 = false; // di lapangan semua mapel punya PSAJ sendiri; Sem 6 tidak menggantikan PSAJ
                    $c->save();
                }
                // Pengaturan: nama kepsek, NIP, dan kode surat disalin; format nomor memakai {kode}/{skl}/{transkrip}/{tahun}.
                // Nomor awal SKL/Transkrip, nomor SK, dan tanggal dikosongkan agar diisi untuk tahun ini.
                $lama = Setting::where('academic_year_id', $src->id)->pluck('value', 'key');
                $isi = [
                    'skl_mulai' => '', 'transkrip_mulai' => '', 'sk_tahun' => substr($new->nama, 5, 4),
                    'kode_surat' => (string) (($lama['kode_surat'] ?? '') ?: '400.3.11'),
                    'nomor_skl' => '{kode}/{skl}/{tahun}', 'nomor_transkrip' => '{kode}/{transkrip}/{tahun}', 'nomor_rapor' => '{kode}/{skl}.1/{tahun}',
                ];
                foreach (['kepsek', 'nip'] as $k) $isi[$k] = (string) ($lama[$k] ?? '');
                foreach (range(1, 6) as $i) $isi["urut_cetak_$i"] = (string) ($lama["urut_cetak_$i"] ?? ''); // urutan kolom cetak nilai semester
                foreach ($isi as $k => $v) {
                    Setting::create(['key' => $k, 'value' => $v, 'academic_year_id' => $new->id]);
                }
            }
            return $new;
        });

        session(['tahun_id' => $new->id]);
        Year::forget();
        return redirect(route('cetak') . '#pengaturan-dokumen')->with('ok', "Tahun pelajaran {$new->nama} dibuat dan dipilih. Isi nomor SKL pertama, nomor Transkrip pertama, nomor SK, dan tanggal kelulusan di bagian Pengaturan dokumen di bawah.");
    }

    /** Tahun yang dibuka otomatis saat login. */
    public function activate(AcademicYear $year)
    {
        AcademicYear::query()->update(['aktif' => false]);
        $year->update(['aktif' => true]);
        return back()->with('ok', "Tahun pelajaran {$year->nama} dijadikan tahun aktif.");
    }

    public function destroy(AcademicYear $year)
    {
        if ($year->aktif) return back()->withErrors(['tahun' => 'Tahun aktif tidak bisa dihapus.']);
        if ($year->jumlahSiswa() > 0) return back()->withErrors(['tahun' => 'Tahun pelajaran yang masih berisi siswa tidak bisa dihapus.']);
        DB::transaction(function () use ($year) {
            Subject::withoutGlobalScopes()->where('academic_year_id', $year->id)->delete();
            Setting::where('academic_year_id', $year->id)->delete();
            $year->delete();
        });
        if ((int) session('tahun_id') === $year->id) session()->forget('tahun_id');
        return redirect()->route('tahun.index')->with('ok', "Tahun pelajaran {$year->nama} dihapus.");
    }
}
