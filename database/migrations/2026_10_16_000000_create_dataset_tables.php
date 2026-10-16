<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // php-stats docs/data/housing.csv
        Schema::create('housing_rentals', function (Blueprint $table) {
            $table->id();
            $table->float('area_m2');
            $table->unsignedInteger('bedrooms');
            $table->float('monthly_rent');
        });

        // php-stats docs/data/schools.csv
        Schema::create('student_scores', function (Blueprint $table) {
            $table->id();
            $table->string('school')->index();
            $table->float('study_hours');
            $table->float('prior_score');
            $table->float('score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_scores');
        Schema::dropIfExists('housing_rentals');
    }
};
