<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->text('value')->nullable(); // stores text/number/date as string, or JSON-encoded array for multi_choice
            $table->timestamps();

            $table->unique(['submission_id', 'question_id']);
            $table->index(['question_id', 'submission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
