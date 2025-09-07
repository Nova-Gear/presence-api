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
        Schema::create('presences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->enum('type', ['checkin', 'checkout']);
            $table->enum('presence_type', ['rfid', 'face_recognition', 'fingerprint', 'manual'])->default('manual');
            $table->string('data')->nullable(); // RFID tag, face image base64, fingerprint data
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamp('presence_time');
            $table->boolean('is_valid')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'presence_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presences');
    }
};
