<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\Sheets;
use App\Support\Year;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $r)
    {
        $kelas = $r->query('kelas');
        $q = trim((string) $r->query('q'));
        $students = Student::query()
            ->when($kelas, fn ($x) => $x->where('kelas', $kelas))
            ->when($q !== '', fn ($x) => $x->where(fn ($y) => $y->where('nama', 'like', "%$q%")->orWhere('nisn', 'like', "%$q%")->orWhere('nis', 'like', "%$q%")))
            ->orderBy('no_urut')->get();

        return view('students.index', ['students' => $students, 'kelasList' => Student::kelasList(), 'kelas' => $kelas, 'q' => $q]);
    }

    public function attendance(Request $r)
    {
        $kelas = $r->query('kelas');
        $students = Student::query()
            ->when($kelas, fn ($query) => $query->where('kelas', $kelas))
            ->orderBy('kelas')
            ->orderBy('no_urut')
            ->get();

        return view('students.attendance', [
            'students' => $students,
            'kelasList' => Student::kelasList(),
            'kelas' => $kelas,
        ]);
    }

    public function create()
    {
        return view('students.form', ['student' => new Student(['no_urut' => (int) Student::max('no_urut') + 1])]);
    }

    public function store(Request $r)
    {
        $st = Student::create($this->data($r));
        return redirect()->route('siswa.index')->with('ok', "Siswa {$st->nama} ditambahkan.");
    }

    public function edit(Student $student)
    {
        return view('students.form', ['student' => $student]);
    }

    public function update(Request $r, Student $student)
    {
        $student->update($this->data($r, $student));
        return redirect()->route('siswa.index')->with('ok', 'Data siswa diperbarui.');
    }

    public function destroy(Student $student)
    {
        $student->delete();
        return redirect()->route('siswa.index')->with('ok', 'Siswa dan seluruh nilainya dihapus.');
    }

    private function data(Request $r, ?Student $st = null): array
    {
        $d = $r->validate([
            'nisn' => ['required', 'string', 'max:20', Rule::unique('students', 'nisn')
                ->where(fn ($query) => $query->where('academic_year_id', Year::id()))
                ->ignore($st?->id)],
            'nis' => 'nullable|string|max:30',
            'nama' => 'required|string|max:255',
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'kelas' => 'required|string|max:20',
            'tempat_lahir' => 'nullable|string|max:255',
            'tgl_lahir' => 'nullable|date',
            'no_ijazah' => 'nullable|string|max:40',
            'no_urut' => 'nullable|integer|min:0',
            'absensi' => 'nullable|array',
            'absensi.*' => 'nullable|integer|min:0|max:999',
        ]);
        $d['no_urut'] = $d['no_urut'] ?? (int) Student::max('no_urut') + 1;
        $d['absensi'] = array_map('intval', array_pad(array_values($d['absensi'] ?? []), 6, 0));
        return $d;
    }

    /* ---------- Ekspor / impor data siswa ---------- */

    /** Ekspor data siswa. ?blank=1 → hanya header (format kosongan untuk impor). */
    public function export(Request $r)
    {
        $blank = $r->boolean('blank');
        $rows = [['No', 'NISN', 'NIS', 'Nama', 'Jenis Kelamin', 'Kelas', 'Tempat Lahir', 'Tgl Lahir', 'No Ijazah']];
        if (!$blank) {
            foreach (Student::orderBy('no_urut')->get() as $s) {
                $rows[] = [$s->no_urut, $s->nisn, $s->nis, $s->nama, $s->jenis_kelamin, $s->kelas, $s->tempat_lahir, $s->tgl_lahir?->format('Y-m-d'), $s->no_ijazah];
            }
        }
        $base = $blank ? 'template-data-siswa' : 'data-siswa';
        return Sheets::download(['Siswa' => $rows], $base, $r->query('format') === 'csv' ? 'csv' : 'xlsx', [1, 2, 3, 4, 5, 6, 8]);
    }

    public function import(Request $r)
    {
        $r->validate(['file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120']);
        $sheets = Sheets::read($r->file('file'));
        $alias = ['nisn' => 'nisn', 'nis' => 'nis', 'nama' => 'nama', 'namasiswa' => 'nama', 'jeniskelamin' => 'jenis_kelamin', 'jeniskelaminlp' => 'jenis_kelamin', 'jk' => 'jenis_kelamin', 'jklp' => 'jenis_kelamin', 'gender' => 'jenis_kelamin', 'kelas' => 'kelas', 'rombel' => 'kelas', 'tempatlahir' => 'tempat_lahir', 'tempat' => 'tempat_lahir',
            'tgllahir' => 'tgl_lahir', 'tanggallahir' => 'tgl_lahir', 'noijazah' => 'no_ijazah', 'nomorijazah' => 'no_ijazah', 'ijazah' => 'no_ijazah',
            'no' => 'no_urut', 'nourut' => 'no_urut'];

        // Cari sheet yang punya kolom NISN (bukan sekadar ambil sheet pertama)
        $rows = [];
        $map = [];
        $bestRows = [];
        $bestMap = [];
        $required = ['nisn' => 'NISN', 'nis' => 'NIS', 'nama' => 'Nama', 'kelas' => 'Kelas'];
        foreach ($sheets as $sheetRows) {
            $try = [];
            foreach (($sheetRows[0] ?? []) as $i => $h) {
                if (isset($alias[Sheets::key($h)])) $try[$alias[Sheets::key($h)]] = $i;
            }
            if (count($try) > count($bestMap)) {
                $bestRows = $sheetRows;
                $bestMap = $try;
            }
            if (!array_diff_key($required, $try)) {
                $rows = $sheetRows;
                $map = $try;
                break;
            }
        }
        if (!$map) {
            $rows = $bestRows;
            $map = $bestMap;
        }
        $missing = [];
        foreach ($required as $field => $label) {
            if (!isset($map[$field])) $missing[] = $label;
        }
        if ($missing) {
            return back()->withErrors(['file' => 'Kolom wajib tidak ditemukan: ' . implode(', ', $missing) . '. Unduh template Data Siswa untuk format yang benar.']);
        }

        $genderErrors = [];
        if (isset($map['jenis_kelamin'])) {
            foreach (array_slice($rows, 1) as $offset => $row) {
                $nisn = Sheets::nisn($row[$map['nisn']] ?? null);
                $rawGender = trim((string) ($row[$map['jenis_kelamin']] ?? ''));
                if ($nisn !== '' && $rawGender !== '' && $this->normalizeGender($rawGender) === null) {
                    $genderErrors[] = 'Baris ' . ($offset + 2) . ': Jenis Kelamin harus L/Laki-laki atau P/Perempuan.';
                }
            }
        }
        if ($genderErrors) return back()->withErrors(['file' => implode(' ', $genderErrors)]);

        $update = $r->boolean('perbarui');
        $baru = $diperbarui = $sudahAda = 0;
        foreach (array_slice($rows, 1) as $row) {
            $v = [];
            foreach ($map as $f => $i) {
                $x = $row[$i] ?? null;
                $v[$f] = match ($f) {
                    'nisn' => Sheets::nisn($x),
                    'tgl_lahir' => Sheets::date($x),
                    'jenis_kelamin' => $this->normalizeGender($x),
                    'no_urut' => (int) $x,
                    default => trim((string) $x),
                };
            }
            if (($v['nisn'] ?? '') === '') continue;
            $cur = Student::withoutGlobalScopes()
                ->where('academic_year_id', Year::id())
                ->where('nisn', $v['nisn'])
                ->first();
            if ($cur) {
                if ($update) {
                    $cur->update(array_filter($v, fn ($x, $k) => $k !== 'nisn' && $x !== '' && $x !== null && $x !== 0, ARRAY_FILTER_USE_BOTH));
                    $diperbarui++;
                } else {
                    $sudahAda++;
                }
            } else {
                $v['no_urut'] = ($v['no_urut'] ?? 0) ?: (int) Student::max('no_urut') + 1;
                $v['nama'] ??= '';
                $v['jenis_kelamin'] ??= null;
                Student::create($v);
                $baru++;
            }
        }
        $message = "Impor siswa selesai: $baru baru, $diperbarui diperbarui.";
        if ($sudahAda) $message .= " $sudahAda dilewati karena NISN sudah ada (centang opsi perbarui untuk mengubah datanya).";
        return redirect()->route('siswa.index')->with('ok', $message);
    }

    private function normalizeGender($value): ?string
    {
        $key = Sheets::key($value);
        return match ($key) {
            'l', 'laki', 'lakilaki', 'pria', 'male' => 'L',
            'p', 'perempuan', 'wanita', 'female' => 'P',
            default => null,
        };
    }
}
