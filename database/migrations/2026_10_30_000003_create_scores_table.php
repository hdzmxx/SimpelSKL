<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scores', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->foreignId('subject_id')->constrained()->cascadeOnDelete();
            for ($i = 1; $i <= 6; $i++) {
                $t->decimal("sem$i", 5, 2)->nullable();
            }
            $t->decimal('tertulis', 5, 2)->nullable();   // PSAJ tertulis
            $t->decimal('praktik', 5, 2)->nullable();    // PSAJ praktik
            $t->timestamps();
            $t->unique(['student_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};
