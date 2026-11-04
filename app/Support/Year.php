<?php

namespace App\Support;

use App\Models\AcademicYear;

/** Tahun pelajaran yang sedang dipilih (disimpan di session); bila belum dipilih: tahun yang ditandai aktif. */
class Year
{
    private static ?AcademicYear $cur = null;
    private static bool $loaded = false;

    public static function current(): ?AcademicYear
    {
        if (self::$loaded) return self::$cur;
        self::$loaded = true;
        try {
            $pilih = (app()->bound('request') && request()->hasSession()) ? session('tahun_id') : null;
            $y = $pilih ? AcademicYear::find($pilih) : null;
            $y ??= AcademicYear::where('aktif', true)->first() ?? AcademicYear::orderByDesc('nama')->first();
            return self::$cur = $y;
        } catch (\Throwable $e) {
            return self::$cur = null; // tabel belum ada (sebelum migrasi)
        }
    }

    public static function id(): ?int
    {
        return self::current()?->id;
    }

    public static function forget(): void
    {
        self::$loaded = false;
        self::$cur = null;
    }
}
