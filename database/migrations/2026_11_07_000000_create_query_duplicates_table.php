<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates the report table in the configured package connection.
     */
    public function up(): void
    {
        Schema::connection($this->connection())->create('query_duplicates', function (Blueprint $table): void {
            $table->id();
            $table->char('report_key', 64)->unique();
            $table->string('method', 10);
            $table->text('path');
            $table->string('route', 255)->nullable();
            $table->text('sql');
            $table->unsignedInteger('query_count');
            $table->decimal('time_ms', 10, 2);
            $table->string('type', 10);
            $table->unsignedInteger('occurrences')->default(0);
            $table->timestamps();

            $table->index('updated_at');
            $table->index('query_count');
            $table->index('time_ms');
            $table->index('occurrences');
        });
    }

    /**
     * Drops the report table from the configured package connection.
     */
    public function down(): void
    {
        Schema::connection($this->connection())->dropIfExists('query_duplicates');
    }

    /**
     * Returns the package connection name or the application default.
     */
    private function connection(): ?string
    {
        $connection = config('query-duplicates.connection');

        return is_string($connection) && $connection !== '' ? $connection : null;
    }
};
