<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Student;
use Illuminate\Support\Collection;

/**
 * Pencocokan siswa dengan kriteria kelulusan yang diatur sekolah (menu Kelulusan).
 * Hasil hitungan hanya saran; keputusan resmi ada pada penetapan (status_lulus) dari rapat dewan guru.
 */
class Kelulusan
{
    public const SIKAP = ['Sangat Baik' => 4, 'Baik' => 3, 'Cukup' => 2, 'Kurang' => 1];

    /** Aturan aktif. Hanya "nilai lengkap" yang menyala bawaan; ambang lain kosong sampai sekolah mengisinya. */
    public static function aturan(): array
    {
        $s = Setting::map();
        $n = fn ($k) => (isset($s[$k]) && $s[$k] !== '' && is_numeric($s[$k])) ? (float) $s[$k] : null;
        $sikap = $s['lulus_sikap'] ?? '';
        return [
            'lengkap' => ($s['lulus_lengkap'] ?? '') !== '0',
            'min_mapel' => $n('lulus_min_mapel'),
            'min_rata' => $n('lulus_min_rata'),
            'sikap' => isset(self::SIKAP[$sikap]) ? $sikap : null,
            'maks_alpa' => $n('lulus_maks_alpa'),
        ];
    }

    /**
     * @param Student $st harus sudah melalui Grading::apply() (fin, avg) dan memuat scores
     * @return array{status:string,alasan:array} status: ok | belum (data belum lengkap) | tidak (belum memenuhi)
     */
    public static function evaluasi(Student $st, Collection $subjects, array $a): array
    {
        $tidak = [];
        $belum = [];
        [$kosong] = Grading::missing($st, $subjects);
        $perluNilai = $a['lengkap'] || $a['min_mapel'] !== null || $a['min_rata'] !== null;
        if ($perluNilai && $kosong > 0) $belum[] = "$kosong isian nilai masih kosong";

        if ($a['sikap'] !== null) {
            if (!$st->sikap) $belum[] = 'sikap belum diisi';
            elseif ((self::SIKAP[$st->sikap] ?? 0) < self::SIKAP[$a['sikap']]) $tidak[] = "sikap {$st->sikap} (minimal {$a['sikap']})";
        }
        if ($a['maks_alpa'] !== null) {
            $ab = $st->absensi ?? [];
            $alpa = (int) ($ab[2] ?? 0) + (int) ($ab[5] ?? 0);
            if ($alpa > $a['maks_alpa']) $tidak[] = "alpa $alpa hari (maksimal " . (int) $a['maks_alpa'] . ')';
        }
        if ($kosong === 0) {   // nilai kosong dihitung 0, jadi ambang hanya dicek bila data lengkap
            if ($a['min_mapel'] !== null) {
                foreach ($subjects as $m) {
                    $v = $st->fin[$m->id] ?? 0;
                    if ($v + 1e-9 < $a['min_mapel']) $tidak[] = $m->kode . ' ' . Grading::fmt($v) . ' < ' . rtrim(rtrim(number_format($a['min_mapel'], 2, ',', ''), '0'), ',');
                }
            }
            if ($a['min_rata'] !== null && ($st->avg ?? 0) + 1e-9 < $a['min_rata']) {
                $tidak[] = 'rata-rata ' . Grading::fmt($st->avg) . ' < ' . rtrim(rtrim(number_format($a['min_rata'], 2, ',', ''), '0'), ',');
            }
        }
        return ['status' => $tidak ? 'tidak' : ($belum ? 'belum' : 'ok'), 'alasan' => array_merge($tidak, $belum)];
    }

    /** Lulus menurut keputusan akhir: penetapan manual bila ada, bila tidak mengikuti hitungan. */
    public static function lulus(Student $st, array $eval): bool
    {
        if ($st->status_lulus === 'lulus') return true;
        if ($st->status_lulus === 'tidak') return false;
        return $eval['status'] === 'ok';
    }
}
