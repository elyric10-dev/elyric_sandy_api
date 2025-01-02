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
        Schema::create('attending_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->onDelete('cascade');
            $table->foreignId('party_member_id')->constrained()->onDelete('cascade');
            $table->foreignId('guest_id')->constrained()->onDelete('cascade');
            $table->foreignId('table_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('status', ['waiting', 'arrived', 'not-arrived'])->default('waiting');
            $table->string('name');
            $table->string('middle')->nullable();
            $table->string('lastname');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attending_guests');
        Schema::dropIfExists('invitations');
        Schema::dropIfExists('party_members');
        Schema::dropIfExists('guests');
    }
};
