@extends('layouts.app')
@section('content')
<h2 class="t">Data Siswa <span style="color:var(--mut);font-weight:400;font-size:14px">· {{ \App\Support\Year::current()?->nama }}</span></h2>

<div class="tb">
  <form method="GET" style="display:contents">
    <select name="kelas" class="sm" onchange="this.form.submit()">
      <option value="">Semua kelas</option>
      @foreach ($kelasList as $k)<option @selected($k === $kelas)>{{ $k }}</option>@endforeach
    </select>
    <input type="search" name="q" value="{{ $q }}" placeholder="Cari nama / NISN / NIS…" style="min-width:160px">
    <button>Cari</button>
  </form>
  <span class="sp"></span>
  <a class="btn p" href="{{ route('siswa.ekspor', ['blank' => 1]) }}">Unduh format kosong</a>
  <a class="btn" href="{{ route('siswa.ekspor') }}">Ekspor data terisi</a>
  <a class="btn" href="{{ route('siswa.ekspor', ['format' => 'csv']) }}">CSV</a>
  <a class="btn" href="{{ route('siswa.create') }}">+ Tambah siswa</a>
</div>

<form class="import-box" method="POST" action="{{ route('siswa.impor') }}" enctype="multipart/form-data">@csrf
  <b>Impor siswa</b>
  <input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required>
  <label class="chk"><input type="checkbox" name="perbarui" value="1">Perbarui NISN yang sudah ada (centang untuk memperbarui data/JK)</label>
  <button>Impor</button>
  <span class="help">Kolom wajib: NISN, NIS, Nama, dan Kelas. Kolom lain boleh tidak ada atau dikosongkan; Jenis Kelamin (L/P), No, Tempat Lahir, Tgl Lahir (YYYY-MM-DD), dan No Ijazah bersifat opsional.</span>
</form>

<div class="tw"><table class="t">
  <thead><tr><th>No</th><th>NISN</th><th>Nama</th><th>Jenis kelamin</th><th>Kelas</th><th>Tempat, tgl lahir</th><th>No ijazah</th><th>Aksi</th></tr></thead>
  <tbody>
  @forelse ($students as $s)
    <tr><td>{{ $s->no_urut }}</td><td>{{ $s->nisn }}</td><td>{{ $s->nama }}</td><td>{{ ['L' => 'Laki-laki', 'P' => 'Perempuan'][$s->jenis_kelamin] ?? '' }}</td><td>{{ $s->kelas }}</td>
      <td>{{ $s->tempat_lahir }}{{ $s->tgl_lahir ? ', ' . $s->tgl_lahir->format('d-m-Y') : '' }}</td><td>{{ $s->no_ijazah }}</td>
      <td class="nw"><a class="btn" href="{{ route('siswa.edit', $s) }}">Edit</a>
        <form class="inline" method="POST" action="{{ route('siswa.destroy', $s) }}" onsubmit="return confirm('Hapus {{ addslashes($s->nama) }} beserta seluruh nilainya?')">@csrf @method('DELETE')<button class="d">Hapus</button></form></td></tr>
  @empty
    <tr><td colspan="8">Tidak ada data.</td></tr>
  @endforelse
  </tbody></table></div>
@endsection
