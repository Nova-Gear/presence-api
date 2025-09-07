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
        Schema::create('presence_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->time('checkin_start'); // e.g., 07:00:00
            $table->time('checkin_end');   // e.g., 09:00:00
            $table->time('checkout_start'); // e.g., 16:00:00
            $table->time('checkout_end');   // e.g., 18:00:00
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presence_configs');
    }
};
