<?php

namespace App\Http\Controllers;

use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Services\ScoreImporter;
use App\Services\Sheets;
use App\Support\Year;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Impor/ekspor nilai: format 1 sheet semua mapel, atau satu sheet per mapel. */
class TransferController extends Controller
{
    public function index()
    {
        return view('transfer.index', ['subjects' => Subject::aktif()->orderBy('urut')->get(), 'kelasList' => Student::kelasList()]);
    }

    public function exportAttendance(Request $r)
    {
        $kelas = $r->query('kelas');
        $blank = $r->boolean('blank');
        $students = Student::when($kelas, fn ($q) => $q->where('kelas', $kelas))->orderBy('no_urut')->get();
        $rows = [
            ['No', 'Nama Siswa', 'NISN', 'NIS', 'Kelas', 'Semester 5', '', '', 'Semester 6', '', ''],
            ['', '', '', '', '', 'Sakit', 'Izin', 'Alpa', 'Sakit', 'Izin', 'Alpa'],
        ];
        foreach ($students as $i => $student) {
            $attendance = array_pad(array_values($student->absensi ?? []), 6, 0);
            $rows[] = [
                $kelas ? $i + 1 : ($student->no_urut ?: $i + 1), $student->nama, $student->nisn, $student->nis, $student->kelas,
                ...($blank ? array_fill(0, 6, null) : array_slice($attendance, 0, 6)),
            ];
        }

        $base = ($blank ? 'template-kehadiran' : 'kehadiran')
            . ($kelas ? '-' . strtolower(str_replace(' ', '', $kelas)) : '');
        if ($r->query('format') === 'csv') {
            return Sheets::download(['Kehadiran' => $rows], $base, 'csv', [2, 3]);
        }

        $book = new Spreadsheet();
        $ws = $book->getActiveSheet();
        $ws->setTitle('Kehadiran');
        foreach (['A' => 'No', 'B' => 'Nama Siswa', 'C' => 'NISN', 'D' => 'NIS', 'E' => 'Kelas'] as $column => $label) {
            $ws->setCellValue($column . '1', $label);
            $ws->mergeCells($column . '1:' . $column . '2');
        }
        $ws->setCellValue('F1', 'Semester 5');
        $ws->mergeCells('F1:H1');
        $ws->setCellValue('I1', 'Semester 6');
        $ws->mergeCells('I1:K1');
        foreach (['F' => 'Sakit', 'G' => 'Izin', 'H' => 'Alpa', 'I' => 'Sakit', 'J' => 'Izin', 'K' => 'Alpa'] as $column => $label) {
            $ws->setCellValue($column . '2', $label);
        }
        foreach (array_slice($rows, 2) as $index => $row) {
            $excelRow = $index + 3;
            foreach ($row as $column => $value) {
                if ($value === null || $value === '') continue;
                $cell = Coordinate::stringFromColumnIndex($column + 1) . $excelRow;
                if (in_array($column, [2, 3], true)) {
                    $ws->setCellValueExplicit($cell, (string) $value, DataType::TYPE_STRING);
                } else {
                    $ws->setCellValue($cell, $value);
                }
            }
        }
        $ws->getStyle('A1:K2')->getFont()->setBold(true);
        $ws->getStyle('A1:K2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        foreach (range('A', 'K') as $column) $ws->getColumnDimension($column)->setAutoSize(true);
        $ws->freezePane('F3');
        return response()->streamDownload(function () use ($book) {
            (new Xlsx($book))->save('php://output');
        }, "$base.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function importAttendance(Request $r)
    {
        $r->validate(['file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240']);
        $alias = [
            'nisn' => 'nisn', 'nama' => 'nama', 'namasiswa' => 'nama', 'nis' => 'nis',
            'sem5sakit' => 0, 'semester5sakit' => 0, 'smt5sakit' => 0, 'sakitsem5' => 0,
            'sem5izin' => 1, 'semester5izin' => 1, 'smt5izin' => 1, 'izinsem5' => 1,
            'sem5alpa' => 2, 'semester5alpa' => 2, 'smt5alpa' => 2, 'alpasem5' => 2,
            'sem6sakit' => 3, 'semester6sakit' => 3, 'smt6sakit' => 3, 'sakitsem6' => 3,
            'sem6izin' => 4, 'semester6izin' => 4, 'smt6izin' => 4, 'izinsem6' => 4,
            'sem6alpa' => 5, 'semester6alpa' => 5, 'smt6alpa' => 5, 'alpasem6' => 5,
            // Header lama tetap diterima; indeks absensinya tidak berubah.
            'sem1sakit' => 0, 'semester1sakit' => 0, 'smt1sakit' => 0, 'sakitsem1' => 0,
            'sem1izin' => 1, 'semester1izin' => 1, 'smt1izin' => 1, 'izinsem1' => 1,
            'sem1alpa' => 2, 'semester1alpa' => 2, 'smt1alpa' => 2, 'alpasem1' => 2,
            'sem2sakit' => 3, 'semester2sakit' => 3, 'smt2sakit' => 3, 'sakitsem2' => 3,
            'sem2izin' => 4, 'semester2izin' => 4, 'smt2izin' => 4, 'izinsem2' => 4,
            'sem2alpa' => 5, 'semester2alpa' => 5, 'smt2alpa' => 5, 'alpasem2' => 5,
        ];
        $rows = [];
        $map = [];
        $foundNisn = false;
        foreach (Sheets::read($r->file('file')) as $sheetRows) {
            $headerRows = 1;
            $headers = $sheetRows[0] ?? [];
            $subHeaders = $sheetRows[1] ?? [];
            if (array_intersect(array_map([Sheets::class, 'key'], $subHeaders), ['sakit', 'izin', 'alpa'])) {
                $headerRows = 2;
                $headers = [];
                $semester = '';
                foreach (array_keys($sheetRows[0] ?? []) as $i) {
                    $top = Sheets::key($sheetRows[0][$i] ?? '');
                    if (preg_match('/^(?:semester|sem|smt)([56])$/', $top, $matches)) {
                        $semester = $matches[1];
                    } elseif ($top !== '' && !preg_match('/^(?:semester|sem|smt)[56]$/', $top)) {
                        $semester = '';
                    }
                    $sub = Sheets::key($subHeaders[$i] ?? '');
                    $headers[$i] = $semester !== '' && in_array($sub, ['sakit', 'izin', 'alpa'], true)
                        ? 'semester' . $semester . $sub
                        : ($sheetRows[0][$i] ?? '');
                }
            }
            $try = [];
            foreach ($headers as $i => $header) {
                $key = Sheets::key($header);
                if ($key === 'nisn') $foundNisn = true;
                if (isset($alias[$key])) {
                    $field = $alias[$key];
                    $try[is_int($field) ? 'absensi_' . $field : $field] = $i;
                }
            }
            if (isset($try['nisn']) && count(array_filter(array_keys($try), fn ($key) => str_starts_with($key, 'absensi_'))) > 0) {
                $rows = $sheetRows;
                $map = $try;
                $dataStart = $headerRows;
                break;
            }
        }
        if (!$map) {
            $message = $foundNisn
                ? 'Kolom kehadiran tidak ditemukan. Pastikan ada kolom Semester 5/6: Sakit, Izin, atau Alpa.'
                : 'Kolom NISN tidak ditemukan pada file.';
            return back()->withErrors(['file' => $message]);
        }

        $attendanceFields = array_filter(array_keys($map), fn ($key) => str_starts_with($key, 'absensi_'));
        $input = [];
        $errors = [];
        foreach (array_slice($rows, $dataStart) as $rowIndex => $row) {
            $nisn = Sheets::nisn($row[$map['nisn']] ?? null);
            if ($nisn === '') continue;
            if (isset($input[$nisn])) {
                $errors[] = 'NISN ' . $nisn . ' muncul lebih dari sekali (baris ' . ($rowIndex + $dataStart + 1) . ').';
                continue;
            }
            $attendance = [];
            foreach ($attendanceFields as $field) {
                $index = (int) substr($field, strlen('absensi_'));
                $value = trim((string) ($row[$map[$field]] ?? ''));
                if ($value === '') {
                    $attendance[$index] = 0;
                } elseif (!is_numeric($value) || (float) $value < 0 || (float) $value > 999 || floor((float) $value) !== (float) $value) {
                    $errors[] = 'Jumlah kehadiran pada baris ' . ($rowIndex + $dataStart + 1) . ' harus bilangan bulat 0–999.';
                    continue 2;
                } else {
                    $attendance[$index] = (int) $value;
                }
            }
            $input[$nisn] = [
                'nama' => trim((string) ($row[$map['nama']] ?? '')),
                'attendance' => $attendance,
            ];
        }
        if ($errors) return back()->withErrors(['file' => implode(' ', $errors)]);

        $students = Student::whereIn('nisn', array_keys($input))->get()->keyBy('nisn');
        $matched = [];
        $unknown = [];
        foreach ($input as $nisn => $entry) {
            $student = $students->get($nisn);
            if (!$student) {
                $unknown[] = ['nisn' => $nisn, 'nama' => $entry['nama']];
                continue;
            }
            $attendance = array_pad(array_values($student->absensi ?? []), 6, 0);
            foreach ($entry['attendance'] as $i => $count) $attendance[$i] = $count;
            $matched[] = [
                'id' => $student->id,
                'nisn' => $student->nisn,
                'nama' => $student->nama,
                'kelas' => $student->kelas,
                'absensi' => array_slice($attendance, 0, 6),
            ];
        }

        session(['import' => [
            'type' => 'attendance',
            'academic_year_id' => Year::id(),
            'name' => $r->file('file')->getClientOriginalName() . ' (kehadiran)',
            'back' => route('kehadiran'),
            'result' => ['rows' => $matched, 'unknown' => $unknown],
        ]]);
        return redirect()->route('impor.pratinjau');
    }

    public function exportAll(Request $r)
    {
        $kelas = $r->query('kelas');
        $blank = $r->boolean('blank');
        $students = Student::when($kelas, fn ($q) => $q->where('kelas', $kelas))->orderBy('no_urut')->get();
        $scores = $blank
            ? collect()
            : Score::whereIn('subject_id', Subject::aktif()->pluck('id'))->get()->groupBy('subject_id')->map(fn ($g) => $g->keyBy('student_id'));

        $sheets = [];
        foreach (Subject::aktif()->orderBy('urut')->get() as $m) {
            $fields = $m->fields();
            $head = ['No', 'NISN', 'Nama', 'Kelas'];
            foreach ($fields as $f) {
                $head[] = match ($f) { 'tertulis' => 'Tertulis', 'praktik' => 'Praktik', default => 'S' . substr($f, 3) };
            }
            $rows = [$head];
            foreach ($students as $i => $s) {
                $sc = $scores->get($m->id)?->get($s->id);
                $row = [$kelas ? $i + 1 : ($s->no_urut ?: $i + 1), $s->nisn, $s->nama, $s->kelas];
                foreach ($fields as $f) $row[] = $blank ? null : $sc?->$f;
                $rows[] = $row;
            }
            $sheets[$m->kode] = $rows;
        }
        $base = ($blank ? 'template-semua-mapel' : 'nilai-semua-mapel') . ($kelas ? '-' . strtolower(str_replace(' ', '', $kelas)) : '');
        return Sheets::download($sheets, $base, 'xlsx', [1, 2, 3]);
    }

    public function importAll(Request $r)
    {
        $r->validate(['file' => 'required|file|mimes:xlsx,xls|max:10240']);
        $result = ScoreImporter::parse(Sheets::read($r->file('file')), Subject::aktif()->orderBy('urut')->get());
        session(['import' => ['name' => $r->file('file')->getClientOriginalName(), 'back' => route('transfer'), 'result' => $result]]);
        return redirect()->route('impor.pratinjau');
    }


    /**
     * Ekspor / template 1 sheet semua mapel (kolom = kode mapel, nilai 1 semester).
     * ?blank=1 kosongkan nilai. ?sem=1..6 semester yang diisi.
     */
    public function exportLeger(Request $r)
    {
        $kelas = $r->query('kelas');
        $blank = $r->boolean('blank');
        $sem = max(1, min(6, (int) $r->query('sem', 1)));
        $field = 'sem' . $sem;

        $subjects = Subject::aktif()->orderBy('urut')->get();
        $students = Student::when($kelas, fn ($q) => $q->where('kelas', $kelas))->orderBy('no_urut')->get();
        $scores = $blank
            ? collect()
            : Score::whereIn('subject_id', $subjects->pluck('id'))->get()
                ->groupBy('subject_id')->map(fn ($g) => $g->keyBy('student_id'));

        $head = ['No', 'Nama Siswa', 'NISN', 'NIS'];
        foreach ($subjects as $m) $head[] = $m->kode;

        $rows = [$head];
        foreach ($students as $i => $s) {
            $row = [$kelas ? $i + 1 : ($s->no_urut ?: $i + 1), $s->nama, $s->nisn, $s->nis];
            foreach ($subjects as $m) {
                $row[] = $blank ? null : ($scores->get($m->id)?->get($s->id)?->$field);
            }
            $rows[] = $row;
        }

        $base = ($blank ? 'template-1sheet-sem' : 'nilai-1sheet-sem') . $sem
            . ($kelas ? '-' . strtolower(str_replace(' ', '', $kelas)) : '');
        return Sheets::download(['Nilai' => $rows], $base, 'xlsx', [2, 3]);
    }

    /**
     * Impor 1 sheet semua mapel → semester yang dipilih.
     * Format: No, Nama, NISN, NIS, lalu kolom kode mapel.
     */
    public function importLeger(Request $r)
    {
        $r->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
            'sem' => 'required|integer|between:1,6',
        ]);
        $sem = (int) $r->input('sem');
        $result = ScoreImporter::parseLegerSemua(
            Sheets::read($r->file('file')),
            Subject::aktif()->orderBy('urut')->get(),
            $sem
        );
        session(['import' => [
            'name' => $r->file('file')->getClientOriginalName() . " (1 sheet → Sem $sem)",
            'back' => route('transfer'),
            'result' => $result,
        ]]);
        return redirect()->route('impor.pratinjau');
    }


    /**
     * Ekspor / template 1 sheet riwayat: tiap mapel punya Smt1..Smt6.
     * Baris 1 = kode mapel, baris 2 = Smt1..Smt6.
     */
    public function exportLegerRiwayat(Request $r)
    {
        $kelas = $r->query('kelas');
        $blank = $r->boolean('blank');

        $subjects = Subject::aktif()->orderBy('urut')->get();
        $students = Student::when($kelas, fn ($q) => $q->where('kelas', $kelas))->orderBy('no_urut')->get();
        $scores = $blank
            ? collect()
            : Score::whereIn('subject_id', $subjects->pluck('id'))->get()
                ->groupBy('subject_id')->map(fn ($g) => $g->keyBy('student_id'));

        $book = new Spreadsheet();
        $ws = $book->getActiveSheet();
        $ws->setTitle('Riwayat');

        $idLabels = ['No', 'Nama Siswa', 'NISN', 'NIS'];
        $idCount = count($idLabels); // 4
        $semCount = 6;
        // kolom: 4 identitas + tiap mapel (6 smt) + 1 kolom kosong pemisah antar mapel
        $nMapel = $subjects->count();

        // --- Baris 1: identitas (vertikal merge 1-2) + nama mapel (horizontal merge 6 kolom) ---
        // --- Baris 2: Smt1..Smt6 ---
        foreach ($idLabels as $i => $label) {
            $col = $i + 1;
            $ws->setCellValue(Coordinate::stringFromColumnIndex($col) . '1', $label);
            $ws->mergeCells(Coordinate::stringFromColumnIndex($col) . '1:' . Coordinate::stringFromColumnIndex($col) . '2');
        }

        $col = $idCount + 1;
        $mapelRanges = []; // [startCol, endCol] 1-based, for borders
        foreach ($subjects as $mi => $m) {
            if ($mi > 0) {
                // kolom pemisah kosong antar mapel
                $col++;
            }
            $start = $col;
            $ws->setCellValue(Coordinate::stringFromColumnIndex($start) . '1', $m->kode);
            $end = $start + $semCount - 1;
            $ws->mergeCells(
                Coordinate::stringFromColumnIndex($start) . '1:' . Coordinate::stringFromColumnIndex($end) . '1'
            );
            for ($s = 1; $s <= $semCount; $s++) {
                $ws->setCellValue(Coordinate::stringFromColumnIndex($start + $s - 1) . '2', 'Smt' . $s);
            }
            $mapelRanges[] = [$start, $end];
            $col = $end + 1;
        }
        $lastCol = $col - 1;

        // --- Data siswa ---
        $rowNum = 3;
        foreach ($students as $i => $s) {
            $ws->setCellValue('A' . $rowNum, $kelas ? $i + 1 : ($s->no_urut ?: $i + 1));
            $ws->setCellValue('B' . $rowNum, $s->nama);
            $ws->setCellValueExplicit('C' . $rowNum, (string) $s->nisn, DataType::TYPE_STRING);
            $ws->setCellValueExplicit('D' . $rowNum, (string) $s->nis, DataType::TYPE_STRING);

            $col = $idCount + 1;
            foreach ($subjects as $mi => $m) {
                if ($mi > 0) $col++; // skip pemisah
                $sc = $scores->get($m->id)?->get($s->id);
                for ($n = 1; $n <= $semCount; $n++) {
                    $f = 'sem' . $n;
                    $v = $blank ? null : $sc?->$f;
                    if ($v !== null && $v !== '') {
                        $ws->setCellValue(Coordinate::stringFromColumnIndex($col) . $rowNum, $v);
                    }
                    $col++;
                }
            }
            $rowNum++;
        }
        $lastRow = max(2, $rowNum - 1);

        // --- Style ---
        $thin = ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']];
        $thick = ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '334155']];
        $headerFill = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']];
        $headerFont = ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10];
        $center = ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true];

        // Identitas header
        $idLast = Coordinate::stringFromColumnIndex($idCount);
        $ws->getStyle("A1:{$idLast}2")->applyFromArray([
            'font' => $headerFont,
            'fill' => $headerFill,
            'alignment' => $center,
            'borders' => ['allBorders' => $thin],
        ]);

        // Header mapel + smt
        foreach ($mapelRanges as $ri => [$start, $end]) {
            $s = Coordinate::stringFromColumnIndex($start);
            $e = Coordinate::stringFromColumnIndex($end);
            $ws->getStyle("{$s}1:{$e}2")->applyFromArray([
                'font' => $headerFont,
                'fill' => $headerFill,
                'alignment' => $center,
                'borders' => ['allBorders' => $thin],
            ]);
            // garis tebal di tepi kiri/kanan grup mapel
            $ws->getStyle("{$s}1:{$s}{$lastRow}")->getBorders()->getLeft()->applyFromArray($thick);
            $ws->getStyle("{$e}1:{$e}{$lastRow}")->getBorders()->getRight()->applyFromArray($thick);
        }

        // Data borders + alignment
        if ($lastRow >= 3) {
            $lastLetter = Coordinate::stringFromColumnIndex($lastCol);
            $ws->getStyle("A3:{$lastLetter}{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => $thin],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            // nilai rata tengah
            foreach ($mapelRanges as [$start, $end]) {
                $s = Coordinate::stringFromColumnIndex($start);
                $e = Coordinate::stringFromColumnIndex($end);
                $ws->getStyle("{$s}3:{$e}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }

        // Lebar kolom
        $ws->getColumnDimension('A')->setWidth(5);
        $ws->getColumnDimension('B')->setWidth(28);
        $ws->getColumnDimension('C')->setWidth(14);
        $ws->getColumnDimension('D')->setWidth(10);
        $col = $idCount + 1;
        foreach ($subjects as $mi => $m) {
            if ($mi > 0) {
                $ws->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setWidth(2);
                $col++;
            }
            for ($s = 0; $s < $semCount; $s++) {
                $ws->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setWidth(6);
                $col++;
            }
        }

        $ws->getRowDimension(1)->setRowHeight(22);
        $ws->getRowDimension(2)->setRowHeight(18);
        $ws->freezePane('E3');

        $base = ($blank ? 'template-1sheet-riwayat' : 'nilai-1sheet-riwayat')
            . ($kelas ? '-' . strtolower(str_replace(' ', '', $kelas)) : '');

        return response()->streamDownload(function () use ($book) {
            (new Xlsx($book))->save('php://output');
        }, "$base.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * Impor 1 sheet riwayat Smt1..Smt6 per mapel.
     * Cocok dengan leger multi-semester (header mapel + sub Smt1-6).
     */
    public function importLegerRiwayat(Request $r)
    {
        $r->validate(['file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240']);
        $result = ScoreImporter::parseLegerRiwayat(
            Sheets::read($r->file('file')),
            Subject::aktif()->orderBy('urut')->get()
        );
        session(['import' => [
            'name' => $r->file('file')->getClientOriginalName() . ' (1 sheet Smt1–6)',
            'back' => route('transfer'),
            'result' => $result,
        ]]);
        return redirect()->route('impor.pratinjau');
    }

    /** Pratinjau hasil baca (dipakai impor satu mapel maupun semua mapel). */
    public function preview()
    {
        $p = session('import');
        if (!$p) return redirect()->route('transfer')->withErrors(['file' => 'Tidak ada impor yang menunggu. Unggah file terlebih dahulu.']);
        return view('transfer.preview', ['p' => $p]);
    }

    public function apply()
    {
        $p = session('import');
        if (!$p) return redirect()->route('transfer');
        if (($p['type'] ?? null) === 'attendance') {
            if ((int) ($p['academic_year_id'] ?? 0) !== (int) Year::id()) {
                session()->forget('import');
                return redirect()->route('kehadiran')->withErrors(['file' => 'Tahun pelajaran berubah. Silakan impor ulang kehadiran untuk tahun yang dipilih.']);
            }
            $n = DB::transaction(function () use ($p) {
                $saved = 0;
                foreach ($p['result']['rows'] as $row) {
                    $student = Student::withoutGlobalScopes()
                        ->where('academic_year_id', $p['academic_year_id'])
                        ->find($row['id']);
                    if (!$student) continue;
                    $student->absensi = $row['absensi'];
                    $student->save();
                    $saved++;
                }
                return $saved;
            });
            session()->forget('import');
            return redirect($p['back'])->with('ok', "Impor kehadiran selesai: $n siswa diperbarui.");
        }
        $n = DB::transaction(fn () => ScoreImporter::apply($p['result']));
        session()->forget('import');
        return redirect($p['back'])->with('ok', "Impor selesai: $n nilai disimpan.");
    }

    public function cancel()
    {
        $back = session('import.back', route('transfer'));
        session()->forget('import');
        return redirect($back);
    }
}
