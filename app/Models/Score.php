<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Score extends Model
{
    public const FIELDS = ['sem1', 'sem2', 'sem3', 'sem4', 'sem5', 'sem6', 'tertulis', 'praktik'];

    protected $table = 'nilai';

    protected $fillable = ['student_id', 'subject_id', 'sem1', 'sem2', 'sem3', 'sem4', 'sem5', 'sem6', 'tertulis', 'praktik'];

    protected $casts = [
        'sem1' => 'float', 'sem2' => 'float', 'sem3' => 'float', 'sem4' => 'float', 'sem5' => 'float', 'sem6' => 'float',
        'tertulis' => 'float', 'praktik' => 'float',
    ];
}
