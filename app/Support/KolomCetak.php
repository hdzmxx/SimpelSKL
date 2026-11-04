<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * Urutan kolom mapel untuk CETAK nilai semester (per semester dan per tahun pelajaran).
 * Hanya dipakai di cetak nilai semester; SKL, rapor, transkrip, dan tabel lain memakai urutan mapel.
 */
class KolomCetak
{
    /** Mapel (aktif) dalam urutan tersimpan untuk semester $n; yang belum ada di urutan tersimpan ditaruh di belakang menurut urutan mapel. */
    public static function urutan(Collection $subjects, int $n): Collection
    {
        $raw = (string) (Setting::map()["urut_cetak_$n"] ?? '');
        $kodes = $raw !== '' ? (array) json_decode($raw, true) : [];
        if (!$kodes) return $subjects->values();
        $by = $subjects->keyBy('kode');
        $out = collect();
        foreach ($kodes as $k) {
            if (is_string($k) && $by->has($k)) {
                $out->push($by->get($k));
                $by->forget($k);
            }
        }
        foreach ($subjects as $m) {
            if ($by->has($m->kode)) $out->push($m);
        }
        return $out;
    }
}
