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
        Schema::create('kids', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invitation_id');
            $table->unsignedBigInteger('party_member_id');
            $table->string('name');
            $table->string('middle')->nullable();
            $table->string('lastname');
            $table->foreign('invitation_id')->references('id')->on('invitations')->onDelete('cascade');
            $table->foreign('party_member_id')->references('id')->on('party_members')->onDelete('cascade');
            $table->boolean('is_attending')->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kids');
    }
};
