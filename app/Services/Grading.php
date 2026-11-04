<?php

namespace App\Services;

use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/** Perhitungan nilai akhir (setara MASTER NILAI -> NILAI AKHIR di Excel). */
class Grading
{
    private const BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    /** Rincian satu mapel untuk satu siswa. Nilai kosong dihitung 0 (seperti Excel). */
    public static function detail(Subject $m, ?Score $sc): array
    {
        $sem = [];
        for ($i = 1; $i <= 6; $i++) {
            $sem[] = (float) ($sc?->{"sem$i"} ?? 0);
        }
        $rata = array_sum($sem) / 6;
        $t = $m->ujian_dari_sem6 ? $sem[5] : (float) ($sc?->tertulis ?? 0);
        $p = $m->ada_praktik ? (float) ($sc?->praktik ?? 0) : 0.0;
        $wS = $m->bobot_semester / 100;
        $wT = $m->bobot_tertulis / 100;
        $wP = $m->ada_praktik ? $m->bobot_praktik / 100 : 0.0;

        return ['sem' => $sem, 'rata' => $rata, 'tertulis' => $t, 'praktik' => $p,
            'wS' => $wS, 'wT' => $wT, 'wP' => $wP, 'akhir' => $wS * $rata + $wT * $t + $wP * $p];
    }

    /** Menambahkan ->fin [subject_id => nilai akhir] dan ->avg ke tiap siswa (scores harus sudah di-load). */
    public static function apply(Collection $students, Collection $subjects): Collection
    {
        foreach ($students as $st) {
            $sc = $st->scores->keyBy('subject_id');
            $fin = [];
            foreach ($subjects as $m) {
                $fin[$m->id] = self::detail($m, $sc->get($m->id))['akhir'];
            }
            $st->setAttribute('fin', $fin);
            $st->setAttribute('avg', $fin ? array_sum($fin) / count($fin) : 0.0);
        }
        return $students;
    }

    /**
     * Nilai satu semester (1-6) untuk semua mapel. Menambahkan ->fin [subject_id => nilai atau null], ->jumlah, dan ->avg.
     * Nilai kosong ditampilkan null tetapi dihitung 0 dengan pembagi seluruh mapel aktif.
     */
    public static function applySemester(Collection $students, Collection $subjects, int $n): Collection
    {
        $f = 'sem' . $n;
        foreach ($students as $st) {
            $sc = $st->scores->keyBy('subject_id');
            $fin = [];
            foreach ($subjects as $m) {
                $v = $sc->get($m->id)?->$f;
                $fin[$m->id] = $v === null ? null : (float) $v;
            }
            $st->setAttribute('fin', $fin);
            $st->setAttribute('jumlah', array_sum($fin));
            $st->setAttribute('avg', self::averageIncludingMissing($fin) ?? 0.0);
        }
        return $students;
    }

    /** Rata-rata dengan nilai kosong sebagai 0, tetap memakai seluruh jumlah data sebagai pembagi. */
    public static function averageIncludingMissing(array $values): ?float
    {
        return $values ? array_sum($values) / count($values) : null;
    }

    /**
     * Peringkat paralel (seluruh siswa) dan peringkat kelas. Urut menurut ->avg menurun; seri diurutkan menurut nomor urut.
     * Menambahkan ->r_paralel dan ->r_kelas, dan mengembalikan daftar yang sudah terurut (peringkat paralel).
     */
    public static function rankAll(Collection $students): Collection
    {
        $arr = $students->all();
        usort($arr, fn ($a, $b) => round($b->avg, 8) <=> round($a->avg, 8) ?: $a->no_urut <=> $b->no_urut);
        $per = [];
        foreach ($arr as $i => $st) {
            $per[$st->kelas] = ($per[$st->kelas] ?? 0) + 1;
            $st->setAttribute('r_paralel', $i + 1);
            $st->setAttribute('r_kelas', $per[$st->kelas]);
        }
        return collect($arr);
    }

    /** Peringkat: rata-rata menurun, seri diurutkan menurut nomor urut. */
    public static function ranked(Collection $students): Collection
    {
        return $students->sortBy('no_urut')->sortByDesc('avg')->values();
    }

    /** [jumlah sel kosong, jumlah sel yang seharusnya terisi] */
    public static function missing(Student $st, Collection $subjects): array
    {
        $sc = $st->scores->keyBy('subject_id');
        $miss = 0;
        $req = 0;
        foreach ($subjects as $m) {
            $row = $sc->get($m->id);
            foreach ($m->fields() as $f) {
                $req++;
                if (!$row || $row->$f === null) $miss++;
            }
        }
        return [$miss, $req];
    }

    /** Format 2 desimal koma, pembulatan setengah ke atas seperti Excel. */
    public static function fmt(?float $x): string
    {
        return $x === null ? '-' : number_format(round($x + 1e-9, 2), 2, ',', '');
    }

    public static function tanggal(?CarbonInterface $d): string
    {
        return $d ? $d->format('d') . ' ' . self::BULAN[(int) $d->format('n') - 1] . ' ' . $d->format('Y') : '';
    }
}
