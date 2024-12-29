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
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->onDelete('cascade');
            $table->foreignId('party_member_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('middle')->nullable();
            $table->string('lastname');
            $table->boolean('is_attending')->nullable()->default(null);
            $table->string('replacement_name')->nullable();
            $table->string('replacement_middle')->nullable();
            $table->string('replacement_lastname')->nullable();
            $table->boolean('replacement_is_attending')->nullable()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guests');
        Schema::dropIfExists('invitations');
        Schema::dropIfExists('party_members');
    }
};
