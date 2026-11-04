<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Baca/tulis Excel (.xlsx/.xls) dan CSV, plus pembersih nilai sel. */
class Sheets
{
    /**
     * Unduh satu atau beberapa sheet. $sheets = ['Judul Sheet' => [[baris], ...]].
     * Kolom di $textCols (indeks 0..n) selalu ditulis sebagai teks (mis. NISN agar angka 0 di depan tidak hilang).
     * CSV hanya memuat sheet pertama: pemisah ';' dan desimal koma agar langsung rapi di Excel berbahasa Indonesia.
     */
    public static function download(array $sheets, string $base, string $format = 'xlsx', array $textCols = [])
    {
        if ($format === 'csv') {
            $rows = reset($sheets) ?: [];
            return response()->streamDownload(function () use ($rows) {
                $o = fopen('php://output', 'w');
                fwrite($o, "\xEF\xBB\xBF");
                foreach ($rows as $r) {
                    fputcsv($o, array_map(fn ($v) => is_float($v) || is_int($v) ? str_replace('.', ',', (string) $v) : (string) $v, $r), ';', '"', '\\');
                }
                fclose($o);
            }, "$base.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $book = new Spreadsheet();
        $first = true;
        foreach ($sheets as $title => $rows) {
            $ws = $first ? $book->getActiveSheet() : $book->createSheet();
            $first = false;
            $ws->setTitle(substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '-', (string) $title), 0, 31));
            foreach ($rows as $r => $row) {
                foreach ($row as $c => $v) {
                    if ($v === null || $v === '') continue;
                    $addr = Coordinate::stringFromColumnIndex($c + 1) . ($r + 1);
                    if ((is_int($v) || is_float($v)) && !($r > 0 && in_array($c, $textCols, true))) {
                        $ws->setCellValue($addr, $v);
                    } else {
                        $ws->setCellValueExplicit($addr, (string) $v, DataType::TYPE_STRING);
                    }
                }
            }
            if ($rows) {
                $last = Coordinate::stringFromColumnIndex(count($rows[0]));
                $ws->getStyle("A1:{$last}1")->getFont()->setBold(true);
                $ws->freezePane('A2');
                for ($c = 1; $c <= count($rows[0]); $c++) {
                    $ws->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
                }
            }
        }
        $book->setActiveSheetIndex(0);
        return response()->streamDownload(function () use ($book) {
            (new Xlsx($book))->save('php://output');
        }, "$base.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /** Baca file unggahan -> ['Nama Sheet' => [[sel, ...], ...]]. CSV: satu sheet bernama 'csv'. */
    public static function read(UploadedFile $f): array
    {
        $ext = strtolower($f->getClientOriginalExtension());
        if (in_array($ext, ['csv', 'txt'], true)) {
            $txt = preg_replace('/^\xEF\xBB\xBF/', '', (string) file_get_contents($f->getRealPath()));
            $lines = array_values(array_filter(preg_split('/\r\n|\n|\r/', $txt), fn ($l) => trim($l) !== ''));
            if (!$lines) return ['csv' => []];
            $d = ';';
            $best = 0;
            foreach ([';', "\t", ','] as $cand) {
                $n = substr_count($lines[0], $cand);
                if ($n > $best) { $best = $n; $d = $cand; }
            }
            return ['csv' => array_map(fn ($l) => str_getcsv($l, $d, '"', '\\'), $lines)];
        }

        $book = IOFactory::load($f->getRealPath());
        $out = [];
        foreach ($book->getWorksheetIterator() as $ws) {
            $out[$ws->getTitle()] = $ws->toArray(null, true, false, false);
        }
        return $out;
    }

    /** Kunci header: huruf kecil tanpa spasi/simbol ("Tgl Lahir" -> "tgllahir"). */
    public static function key($h): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $h));
    }

    /** NISN 10 digit; angka 0 di depan yang hilang (karena dibuka di Excel) dipulihkan. */
    public static function nisn($v): string
    {
        if (is_int($v) || is_float($v)) $v = sprintf('%.0f', $v);
        $v = preg_replace('/\.0+$/', '', trim((string) $v));
        return preg_match('/^\d{1,9}$/', $v) ? str_pad($v, 10, '0', STR_PAD_LEFT) : $v;
    }

    /** Angka 0-100 atau null bila kosong/tidak valid. Menerima koma desimal. */
    public static function num($v): ?float
    {
        if ($v === null || $v === '') return null;
        if (is_string($v)) {
            $v = str_replace(',', '.', trim($v));
            if ($v === '' || !is_numeric($v)) return null;
        }
        return (is_int($v) || is_float($v) || is_numeric($v)) ? max(0.0, min(100.0, (float) $v)) : null;
    }

    /** Tanggal dari serial Excel, YYYY-MM-DD, atau DD/MM/YYYY -> 'Y-m-d' atau null. */
    public static function date($v): ?string
    {
        if ($v === null || $v === '') return null;
        try {
            if (is_int($v) || is_float($v) || (is_string($v) && is_numeric($v) && (float) $v > 20000)) {
                return XlDate::excelToDateTimeObject((float) $v)->format('Y-m-d');
            }
            $v = trim((string) $v);
            if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $v, $m)) return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
            if (preg_match('/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})$/', $v, $m)) return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        } catch (\Throwable $e) {
        }
        return null;
    }
}
