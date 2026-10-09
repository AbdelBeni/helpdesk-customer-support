<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            $table->string('ticket_number', 30)->unique();

            $table->foreignId('customer_id')
                ->constrained('users');

            $table->foreignId('category_id')
                ->constrained('ticket_categories');

            $table->foreignId('priority_id')
                ->constrained('ticket_priorities');

            $table->foreignId('status_id')
                ->constrained('ticket_statuses');

            $table->string('subject');
            $table->text('description');

            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->index(['status_id', 'priority_id']);
            $table->index(['customer_id', 'status_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
