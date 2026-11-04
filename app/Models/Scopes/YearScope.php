<?php

namespace App\Models\Scopes;

use App\Support\Year;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/** Membatasi query siswa/mapel ke tahun pelajaran yang sedang dipilih. */
class YearScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($id = Year::id()) {
            $builder->where($model->getTable() . '.academic_year_id', $id);
        }
    }
}
