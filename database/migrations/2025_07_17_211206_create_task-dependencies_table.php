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
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->bigIncrements('task_dep_id');
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('depend_id');


            $table->foreign('task_id')->references('task_id')->on('tasks');
            $table->foreign('depend_id')->references('task_id')->on('tasks');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_dependencies');
    }
};
