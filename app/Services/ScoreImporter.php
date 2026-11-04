<?php

namespace App\Services;

use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Support\Collection;

/**
 * Membaca file nilai per mapel. Format: satu baris per siswa, kolom NISN, S1..S6, Tertulis, Praktik.
 * Siswa dicocokkan lewat NISN. Sel kosong dilewati (tidak menghapus nilai yang sudah ada).
 */
class ScoreImporter
{
    /** Peta header -> nama kolom database. */
    public static function headerMap(array $head): array
    {
        $map = [];
        foreach ($head as $i => $h) {
            $k = Sheets::key($h);
            if ($k === 'nisn') $map['nisn'] = $i;
            elseif (preg_match('/^s(?:em(?:ester)?)?([1-6])$/', $k, $m)) $map['sem' . $m[1]] = $i;
            elseif (in_array($k, ['t', 'tertulis', 'psajtertulis', 'ujiantertulis'], true)) $map['tertulis'] = $i;
            elseif (in_array($k, ['p', 'praktik', 'psajpraktik', 'ujianpraktik'], true)) $map['praktik'] = $i;
        }
        return $map;
    }

    /** Sheet milik mapel: sheet berjudul sama dengan kode; untuk impor satu mapel boleh memakai sheet pertama. */
    private static function sheetFor(array $sheets, Subject $m, bool $fallbackFirst): ?array
    {
        foreach ($sheets as $title => $rows) {
            if (strcasecmp(trim((string) $title), $m->kode) === 0) return $rows;
        }
        return $fallbackFirst && $sheets ? reset($sheets) : null;
    }

    /**
     * @param Collection<int,Subject> $subjects semua mapel
     * @param Subject|null $only bila diisi: hanya mapel ini (impor satu mapel)
     * @return array ['subjects' => [subject_id => [...]], 'warnings' => [...]]
     */
    public static function parse(array $sheets, Collection $subjects, ?Subject $only = null): array
    {
        $byNisn = [];
        foreach (Student::query()->pluck('id', 'nisn') as $nisn => $id) {
            $byNisn[Sheets::nisn($nisn)] = $id;
        }
        $existing = Score::whereIn('subject_id', $subjects->pluck('id'))->get()->groupBy('subject_id')->map(fn ($g) => $g->keyBy('student_id'));
        $out = ['subjects' => [], 'warnings' => []];

        $targets = $only ? collect([$only]) : $subjects;
        foreach ($targets as $m) {
            $rows = self::sheetFor($sheets, $m, (bool) $only);
            if ($rows === null) continue;

            $map = self::headerMap($rows[0] ?? []);
            if (!isset($map['nisn'])) {
                $out['warnings'][] = "Sheet {$m->kode}: kolom NISN tidak ditemukan di baris pertama.";
                continue;
            }
            $fields = array_intersect(array_keys($map), $m->fields());
            $skipped = array_diff(array_keys($map), $m->fields(), ['nisn']);
            if ($skipped) {
                $out['warnings'][] = "Sheet {$m->kode}: kolom " . implode(', ', $skipped) . ' diabaikan (tidak dipakai mapel ini).';
            }

            $r = ['kode' => $m->kode, 'nama' => $m->nama, 'rows' => [], 'baris' => 0, 'unknown' => [], 'nilai' => 0, 'berubah' => 0];
            foreach (array_slice($rows, 1) as $row) {
                $nisn = Sheets::nisn($row[$map['nisn']] ?? '');
                if ($nisn === '') continue;
                $r['baris']++;
                $sid = $byNisn[$nisn] ?? null;
                if (!$sid) {
                    $r['unknown'][] = $nisn;
                    continue;
                }
                $vals = [];
                foreach ($fields as $f) {
                    $v = Sheets::num($row[$map[$f]] ?? null);
                    if ($v !== null) $vals[$f] = $v;
                }
                if (!$vals) continue;
                $cur = $existing->get($m->id)?->get($sid);
                foreach ($vals as $f => $v) {
                    $r['nilai']++;
                    $old = $cur?->$f;
                    if ($old === null || abs($old - $v) > 1e-9) $r['berubah']++;
                }
                $r['rows'][$sid] = $vals;
            }
            $out['subjects'][$m->id] = $r;
        }
        return $out;
    }

    /**
     * Format lebar per semester: satu baris per siswa, kolom NISN lalu satu kolom per mapel (judul = kode mapel,
     * urutan bebas). Sheet berjudul "Sem 1".."Sem 6" dibaca sebagai semester itu; file CSV/satu sheet memakai $fallback.
     * Sel kosong dilewati; nilai yang sudah ada ditimpa setelah pratinjau disetujui.
     */
    public static function parseSemester(array $sheets, Collection $subjects, int $fallback): array
    {
        $byNisn = [];
        foreach (Student::query()->pluck('id', 'nisn') as $nisn => $id) {
            $byNisn[Sheets::nisn($nisn)] = $id;
        }
        $existing = Score::whereIn('subject_id', $subjects->pluck('id'))->get()->groupBy('subject_id')->map(fn ($g) => $g->keyBy('student_id'));
        $out = ['subjects' => [], 'warnings' => []];
        foreach ($sheets as $title => $rows) {
            if (preg_match('/^\s*sem(?:ester)?\s*([1-6])\s*$/i', (string) $title, $mm)) {
                $n = (int) $mm[1];
            } elseif (count($sheets) === 1) {
                $n = $fallback;
            } else {
                $out['warnings'][] = "Sheet \"$title\" dilewati: judul sheet harus Sem 1 sampai Sem 6.";
                continue;
            }
            $head = $rows[0] ?? [];
            $nisnCol = null;
            $keys = [];
            foreach ($head as $i => $h) {
                $k = Sheets::key($h);
                if ($k === 'nisn') $nisnCol = $nisnCol ?? $i;
                elseif ($k !== '' && !isset($keys[$k])) $keys[$k] = $i;
            }
            if ($nisnCol === null) {
                $out['warnings'][] = "Sheet $title: kolom NISN tidak ditemukan di baris pertama.";
                continue;
            }
            $f = 'sem' . $n;
            $unknown = [];
            $dibaca = false;
            foreach ($subjects as $m) {
                $col = $keys[Sheets::key($m->kode)] ?? $keys[Sheets::key($m->nama_rapor)] ?? $keys[Sheets::key($m->nama)] ?? null;
                if ($col === null) continue;
                $dibaca = true;
                $r = $out['subjects'][$m->id] ?? ['kode' => $m->kode, 'nama' => $m->nama, 'rows' => [], 'baris' => 0, 'unknown' => [], 'nilai' => 0, 'berubah' => 0];
                foreach (array_slice($rows, 1) as $row) {
                    $nisn = Sheets::nisn($row[$nisnCol] ?? '');
                    if ($nisn === '') continue;
                    $r['baris']++;
                    $sid = $byNisn[$nisn] ?? null;
                    if (!$sid) {
                        $unknown[$nisn] = trim((string) ($row[1] ?? ''));
                        continue;
                    }
                    $v = Sheets::num($row[$col] ?? null);
                    if ($v === null) continue;
                    $r['nilai']++;
                    $old = $existing->get($m->id)?->get($sid)?->$f;
                    if ($old === null || abs($old - $v) > 1e-9) $r['berubah']++;
                    $r['rows'][$sid][$f] = $v;
                }
                $out['subjects'][$m->id] = $r;
            }
            if (!$dibaca) $out['warnings'][] = "Sheet $title: tidak ada kolom yang cocok dengan kode mapel (" . $subjects->pluck('kode')->implode(', ') . ').';
            if ($unknown) {
                $u = array_keys($unknown);
                $out['warnings'][] = "Sheet $title (semester $n): " . count($u) . ' NISN tidak ditemukan di tahun pelajaran ini (' . self::daftarTakDikenal($unknown) . ').';
            }
        }
        return $out;
    }


    /**
     * Format 1 sheet semua mapel (leger): kolom NISN + satu kolom per kode/nama mapel.
     * Tiap sel = nilai untuk semester $sem. Judul di baris atas diabaikan.
     * Sheet boleh banyak (mis. satu kelas per sheet).
     */
    public static function parseLegerSemua(array $sheets, Collection $subjects, int $sem): array
    {
        $sem = max(1, min(6, $sem));
        $field = 'sem' . $sem;
        $byNisn = [];
        foreach (Student::query()->pluck('id', 'nisn') as $nisn => $id) {
            $byNisn[Sheets::nisn($nisn)] = $id;
        }
        $existing = Score::whereIn('subject_id', $subjects->pluck('id'))->get()
            ->groupBy('subject_id')->map(fn ($g) => $g->keyBy('student_id'));

        // alias header → subject_id
        $aliases = [];
        foreach ($subjects as $m) {
            foreach ([$m->kode, $m->nama, $m->nama_rapor] as $label) {
                $k = Sheets::key($label);
                if ($k !== '') $aliases[$k] = $m->id;
            }
            $extra = match (strtoupper($m->kode)) {
                'PAI' => ['paidbp', 'pakdbp', 'paid', 'pakd', 'pendidikanagamaislamdanbudipekerti', 'pendidikanagamadanbudipekerti', 'agamaislam'],
                'PP' => ['pendidikanpancasila', 'ppkn', 'pkn', 'pancasila'],
                'BI' => ['bahasaindonesia', 'bindo', 'bhsindonesia'],
                'MTK' => ['matematika', 'matematikaumum', 'mu'],
                'IPA' => ['ipai', 'ilmunpengetahuanalam', 'ilmpengetahuanalam'],
                'IPS' => ['ipsi', 'ilmunpengetahuansosial'],
                'BING' => ['bahasainggris', 'binggris', 'bhsinggris'],
                'PJOK' => ['pjodk', 'penjas', 'pendidikanjasmaniolahragadankesehatan', 'olahraga'],
                'IT' => ['informatika', 'tik', 'komputer'],
                'PKY' => ['prakarya', 'sbdp', 'senibudaya'],
                'BJ' => ['mlbd', 'muatanlokal', 'bahasadaerah', 'bdkb'],
                default => [],
            };
            foreach ($extra as $e) {
                $k = Sheets::key($e);
                if ($k !== '' && !isset($aliases[$k])) $aliases[$k] = $m->id;
            }
        }

        $out = ['subjects' => [], 'warnings' => []];
        $matched = [];

        foreach ($sheets as $title => $rows) {
            if (!$rows) continue;
            // cari baris header berisi NISN (lewati judul sekolah/kelas)
            $headRow = null;
            $nisnCol = null;
            $colMap = [];
            $scan = min(15, count($rows));
            for ($i = 0; $i < $scan; $i++) {
                $tmpNisn = null;
                $tmpMap = [];
                foreach ($rows[$i] ?? [] as $c => $h) {
                    $k = Sheets::key($h);
                    if ($k === 'nisn') $tmpNisn = $c;
                    elseif (isset($aliases[$k])) $tmpMap[$aliases[$k]] = $c;
                }
                if ($tmpNisn !== null) {
                    $headRow = $i;
                    $nisnCol = $tmpNisn;
                    $colMap = $tmpMap;
                    break;
                }
            }
            if ($headRow === null) {
                $out['warnings'][] = "Sheet \"$title\": kolom NISN tidak ditemukan.";
                continue;
            }
            if (!$colMap) {
                $out['warnings'][] = "Sheet \"$title\": tidak ada kolom mapel yang cocok (" . $subjects->pluck('kode')->implode(', ') . ').';
                continue;
            }

            $unknown = [];
            foreach (array_slice($rows, $headRow + 1) as $row) {
                $nisn = Sheets::nisn($row[$nisnCol] ?? '');
                if ($nisn === '') continue;

                $sid = $byNisn[$nisn] ?? null;
                if (!$sid) {
                    if (self::isSummaryRow($row)) continue;
                    $unknown[$nisn] = trim((string) ($row[1] ?? ''));
                    continue;
                }
                foreach ($colMap as $subjectId => $col) {
                    $v = Sheets::num($row[$col] ?? null);
                    if ($v === null) continue;
                    $m = $subjects->firstWhere('id', $subjectId);
                    if (!$m) continue;
                    $matched[$m->kode] = true;
                    $r = $out['subjects'][$subjectId] ?? ['kode' => $m->kode, 'nama' => $m->nama, 'rows' => [], 'baris' => 0, 'unknown' => [], 'nilai' => 0, 'berubah' => 0];
                    $r['baris']++;
                    $r['nilai']++;
                    $old = $existing->get($subjectId)?->get($sid)?->$field;
                    if ($old === null || abs($old - $v) > 1e-9) $r['berubah']++;
                    $r['rows'][$sid][$field] = $v;
                    $out['subjects'][$subjectId] = $r;
                }
            }
            if ($unknown) {
                $u = array_keys($unknown);
                $out['warnings'][] = "Sheet \"$title\": " . count($u) . ' NISN tidak ditemukan (' . self::daftarTakDikenal($unknown) . ').';
            }
        }

        if (!$out['subjects']) {
            $out['warnings'][] = 'Tidak ada nilai yang terbaca. Pastikan file 1 sheet berisi kolom NISN + kode mapel.';
        } else {
            $miss = $subjects->pluck('kode')->filter(fn ($k) => !isset($matched[$k]))->values();
            if ($miss->isNotEmpty()) {
                $out['warnings'][] = 'Mapel tanpa kolom di file: ' . $miss->implode(', ') . ' (dilewati).';
            }
        }
        return $out;
    }


    /**
     * Format 1 sheet riwayat: header mapel + sub-header Smt1..Smt6 (dan opsional "rerata").
     * Cocok dengan leger multi-semester sekolah. Semua semester terisi sekaligus.
     */
    public static function parseLegerRiwayat(array $sheets, Collection $subjects): array
    {
        $byNisn = [];
        foreach (Student::query()->pluck('id', 'nisn') as $nisn => $id) {
            $byNisn[Sheets::nisn($nisn)] = $id;
        }
        $existing = Score::whereIn('subject_id', $subjects->pluck('id'))->get()
            ->groupBy('subject_id')->map(fn ($g) => $g->keyBy('student_id'));

        $aliases = [];
        foreach ($subjects as $m) {
            foreach ([$m->kode, $m->nama, $m->nama_rapor] as $label) {
                $k = Sheets::key($label);
                if ($k !== '') $aliases[$k] = $m->id;
            }
            $extra = match (strtoupper($m->kode)) {
                'PAI' => ['paidbp', 'pakdbp', 'paid', 'pakd', 'pendidikanagamaislamdanbudipekerti', 'pendidikanagamadanbudipekerti', 'agamaislam', 'pendidikanagamakristendanbudipekerti', 'pendidikanagamakristen'],
                'PP' => ['pendidikanpancasila', 'ppkn', 'pkn', 'pancasila'],
                'BI' => ['bahasaindonesia', 'bindo', 'bhsindonesia'],
                'MTK' => ['matematika', 'matematikaumum', 'mu'],
                'IPA' => ['ipai', 'ilmunpengetahuanalam', 'ilmpengetahuanalam'],
                'IPS' => ['ipsi', 'ilmunpengetahuansosial'],
                'BING' => ['bahasainggris', 'binggris', 'bhsinggris'],
                'PJOK' => ['pjodk', 'penjas', 'pendidikanjasmaniolahragadankesehatan', 'olahraga'],
                'IT' => ['informatika', 'tik', 'komputer'],
                'PKY' => ['prakarya', 'sbdp', 'senibudaya'],
                'BJ' => ['mlbd', 'muatanlokal', 'bahasadaerah', 'bdkb'],
                'SM' => ['senimusik', 'seni'],
                default => [],
            };
            foreach ($extra as $e) {
                $k = Sheets::key($e);
                if ($k !== '' && !isset($aliases[$k])) $aliases[$k] = $m->id;
            }
        }

        $out = ['subjects' => [], 'warnings' => []];
        $matched = [];

        foreach ($sheets as $title => $rows) {
            if (count($rows) < 2) continue;

            $nisnCol = null;
            $mapelRow = null;
            $smtRow = null;
            $scan = min(12, count($rows));
            for ($i = 0; $i < $scan; $i++) {
                $keys = array_map(fn ($h) => Sheets::key($h), $rows[$i] ?? []);
                $hasNisn = in_array('nisn', $keys, true);
                $smtCount = 0;
                foreach ($keys as $k) {
                    if (preg_match('/^(?:smt|sem(?:ester)?)[1-6]$/', $k)) $smtCount++;
                }
                if ($hasNisn) {
                    $nisnCol = array_search('nisn', $keys, true);
                    $mapelRow = $i;
                }
                if ($smtCount >= 2) {
                    $smtRow = $i;
                    if ($mapelRow === null) $mapelRow = max(0, $i - 1);
                }
            }
            if ($nisnCol === null) {
                $out['warnings'][] = "Sheet \"$title\": kolom NISN tidak ditemukan.";
                continue;
            }
            if ($smtRow === null) {
                $out['warnings'][] = "Sheet \"$title\": tidak menemukan sub-header Smt1..Smt6.";
                continue;
            }

            $smtKeys = array_map(fn ($h) => Sheets::key($h), $rows[$smtRow] ?? []);
            $mapelKeys = array_map(fn ($h) => Sheets::key($h), $rows[$mapelRow] ?? []);

            // Propagasi nama mapel ke kanan (merged cells)
            $lastMapel = '';
            $mapelAt = [];
            $maxCol = max(count($smtKeys), count($mapelKeys));
            for ($c = 0; $c < $maxCol; $c++) {
                $mk = $mapelKeys[$c] ?? '';
                if ($mk !== '' && $mk !== 'nisn' && $mk !== 'no' && $mk !== 'nama' && $mk !== 'namasiswa'
                    && $mk !== 'nis' && $mk !== 'rerata' && $mk !== 'rata'
                    && !preg_match('/^(?:smt|sem(?:ester)?)[1-6]$/', $mk)) {
                    $lastMapel = $mk;
                }
                $mapelAt[$c] = $lastMapel;
            }

            $subjectCols = []; // subjectId => [sem1 => col, ...]
            for ($c = 0; $c < $maxCol; $c++) {
                $sk = $smtKeys[$c] ?? '';
                if (!preg_match('/^(?:smt|sem(?:ester)?)([1-6])$/', $sk, $mm)) continue;
                $field = 'sem' . $mm[1];
                $alias = $mapelAt[$c] ?? '';
                if ($alias === '') continue;
                $sid = $aliases[$alias] ?? null;
                if ($sid === null) continue;
                $subjectCols[$sid][$field] = $c;
            }

            if (!$subjectCols) {
                $out['warnings'][] = "Sheet \"$title\": tidak ada grup mapel yang cocok.";
                continue;
            }

            $unknown = [];
            $dataStart = max($mapelRow, $smtRow) + 1;
            foreach (array_slice($rows, $dataStart) as $row) {
                $nisn = Sheets::nisn($row[$nisnCol] ?? '');
                if ($nisn === '') continue;

                $studentId = $byNisn[$nisn] ?? null;
                if (!$studentId) {
                    if (self::isSummaryRow($row)) continue;
                    $unknown[$nisn] = trim((string) ($row[1] ?? ''));
                    continue;
                }
                foreach ($subjectCols as $subjectId => $fields) {
                    $m = $subjects->firstWhere('id', $subjectId);
                    if (!$m) continue;
                    $matched[$m->kode] = true;
                    $r = $out['subjects'][$subjectId] ?? ['kode' => $m->kode, 'nama' => $m->nama, 'rows' => [], 'baris' => 0, 'unknown' => [], 'nilai' => 0, 'berubah' => 0];
                    $r['baris']++;
                    $vals = $r['rows'][$studentId] ?? [];
                    $cur = $existing->get($subjectId)?->get($studentId);
                    foreach ($fields as $field => $col) {
                        $v = Sheets::num($row[$col] ?? null);
                        if ($v === null) continue;
                        $r['nilai']++;
                        $old = $cur?->$field;
                        if ($old === null || abs($old - $v) > 1e-9) $r['berubah']++;
                        $vals[$field] = $v;
                    }
                    if ($vals) $r['rows'][$studentId] = $vals;
                    $out['subjects'][$subjectId] = $r;
                }
            }
            if ($unknown) {
                $u = array_keys($unknown);
                $out['warnings'][] = "Sheet \"$title\": " . count($u) . ' NISN tidak ditemukan (' . self::daftarTakDikenal($unknown) . ').';
            }
        }

        if (!$out['subjects']) {
            $out['warnings'][] = 'Tidak ada nilai yang terbaca. Pastikan file punya header mapel + sub-header Smt1..Smt6.';
        } else {
            $miss = $subjects->pluck('kode')->filter(fn ($k) => !isset($matched[$k]))->values();
            if ($miss->isNotEmpty()) {
                $out['warnings'][] = 'Mapel tanpa kolom di file: ' . $miss->implode(', ') . ' (dilewati).';
            }
        }
        return $out;
    }

    /** Baris ringkasan di bawah tabel (Rata-rata, Jumlah, Total), bukan siswa. Hanya dicek bila NISN tidak cocok dengan siswa mana pun. */
    private static function isSummaryRow(array $row): bool
    {
        $label = mb_strtolower(trim((string) ($row[0] ?? '') . ' ' . (string) ($row[1] ?? '')));
        return (bool) preg_match('/(^|\s)(rata[-\s]?rata|rerata|jumlah|total)(\s|$)/u', $label);
    }

    /** "NISN (nama)" untuk 6 pertama, supaya mudah dicari di file. */
    private static function daftarTakDikenal(array $unknown): string
    {
        $out = [];
        foreach (array_slice($unknown, 0, 6, true) as $nisn => $nama) {
            $out[] = $nama !== '' && $nama !== true ? "$nisn ($nama)" : (string) $nisn;
        }
        return implode(', ', $out) . (count($unknown) > 6 ? '…' : '');
    }

    /** Simpan hasil parse() ke database. Mengembalikan jumlah sel yang ditulis. */
    public static function apply(array $result): int
    {
        $n = 0;
        foreach ($result['subjects'] as $subjectId => $r) {
            foreach ($r['rows'] as $studentId => $vals) {
                Score::updateOrCreate(['student_id' => $studentId, 'subject_id' => $subjectId], $vals);
                $n += count($vals);
            }
        }
        return $n;
    }
}
