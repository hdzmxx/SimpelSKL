<?php

namespace App\Models;

use App\Support\Year;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'pengaturan';

    /** Pengaturan yang berlaku per tahun pelajaran. Sisanya (identitas sekolah, logo) berlaku untuk semua tahun. */
    public const YEAR_KEYS = ['kepsek', 'nip', 'sk_nomor', 'sk_tahun', 'sk_tanggal', 'tanggal_rapat', 'sk_menimbang', 'sk_mengingat', 'tgl_lulus', 'ttd_skl', 'ttd_transkrip',
        'ttd_rapor', 'skl_mulai', 'transkrip_mulai', 'kode_surat', 'nomor_skl', 'nomor_transkrip', 'nomor_rapor',
        'urut_cetak_1', 'urut_cetak_2', 'urut_cetak_3', 'urut_cetak_4', 'urut_cetak_5', 'urut_cetak_6',
        'lulus_lengkap', 'lulus_min_mapel', 'lulus_min_rata', 'lulus_sikap', 'lulus_maks_alpa'];

    protected $fillable = ['key', 'value', 'academic_year_id'];

    protected static ?array $cache = null;
    protected static ?int $cacheYear = null;

    /** Semua pengaturan = bawaan (config/sekolah.php) ditimpa nilai dari database (umum, lalu milik tahun pelajaran terpilih). */
    public static function map(): array
    {
        $y = Year::id();
        if (static::$cache === null || static::$cacheYear !== $y) {
            $global = [];
            $perYear = [];
            foreach (static::query()->get() as $row) {
                $v = (string) $row->value;
                if ($row->academic_year_id === null) {
                    if (!in_array($row->key, self::YEAR_KEYS, true)) $global[$row->key] = $v;
                } elseif ($y !== null && (int) $row->academic_year_id === $y) {
                    $perYear[$row->key] = $v;
                }
            }
            $base = config('sekolah.pengaturan');
            foreach (self::YEAR_KEYS as $k) $base[$k] = ''; // tahun baru: kosong sampai diisi, jangan membawa tanggal tahun lalu
            $base['tahun_ajaran'] = Year::current()?->nama ?? ($base['tahun_ajaran'] ?? '');
            static::$cache = array_merge($base, $global, $perYear);
            static::$cacheYear = $y;
        }
        return static::$cache;
    }

    public static function put(array $kv): void
    {
        $y = Year::id();
        foreach ($kv as $k => $v) {
            static::updateOrCreate(
                ['key' => $k, 'academic_year_id' => in_array($k, self::YEAR_KEYS, true) ? $y : null],
                ['value' => (string) $v]
            );
        }
        static::$cache = null;
    }

    /** Nomor dokumen dari format, mis. 400.3.11/{skl}/2026. */
    public static function nomor(string $key, Student $st): string
    {
        $s = static::map();
        $tahun = ($s['sk_tahun'] ?? '') ?: substr((string) (Year::current()?->nama ?? ''), 5, 4);
        return strtr($s[$key] ?? '', [
            '{kode}' => (string) ($s['kode_surat'] ?? ''), '{tahun}' => $tahun,
            '{transkrip}' => (string) (((int) (($s['transkrip_mulai'] ?? '') ?: 1)) + $st->no_urut - 1),
            '{no}' => (string) $st->no_urut,
            '{skl}' => (string) (((int) ($s['skl_mulai'] ?: 1)) + $st->no_urut - 1),
            '{ijazah}' => (string) $st->no_ijazah, '{nis}' => (string) $st->nis, '{nisn}' => (string) $st->nisn,
        ]);
    }
}
