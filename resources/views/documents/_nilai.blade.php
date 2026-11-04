@use('App\Services\Grading')
<table class="grid">
  <thead><tr><th style="width:9%">No</th><th>Mata Pelajaran</th><th style="width:18%">Nilai</th></tr></thead>
  <tbody>
  @foreach ($subjects as $i => $m)
    <tr><td class="c">{{ $i + 1 }}</td><td>{{ $m->nama }}</td><td class="c">{{ Grading::fmt($st->fin[$m->id]) }}</td></tr>
  @endforeach
  <tr><td colspan="2" class="c"><b>Rata-rata</b></td><td class="c"><b>{{ Grading::fmt($st->avg) }}</b></td></tr>
  </tbody>
</table>
