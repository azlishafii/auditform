<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('draft'); // draft | open | closed
            $table->timestamp('closes_at')->nullable();
            $table->boolean('collect_identity')->default(true);
            $table->boolean('identity_required')->default(false);
            $table->boolean('allow_multiple')->default(false);
            $table->text('thank_you')->nullable();
            $table->timestamps();
        });

        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('label');
            $table->text('help')->nullable();
            $table->boolean('required')->default(false);
            $table->json('options')->nullable();
            $table->json('settings')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('form_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->string('respondent_name')->nullable();
            $table->string('respondent_unit')->nullable();
            $table->timestamps();
        });

        Schema::create('response_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_response_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_field_id')->constrained()->cascadeOnDelete();
            $table->json('value')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->index(['form_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('response_answers');
        Schema::dropIfExists('form_responses');
        Schema::dropIfExists('form_fields');
        Schema::dropIfExists('forms');
    }
};
