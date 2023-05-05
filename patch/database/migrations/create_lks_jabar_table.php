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
        Schema::dropIfExists('polls');
        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->string('title', 191);
            $table->text('description');
            $table->dateTime('deadline');
            $table->bigInteger('created_by')->unsigned();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('divisions');
        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->timestamps();
        });
        DB::table('divisions')->insert(
            array(
                ['name' => 'Finance'],
                ['name' => 'Payment'],
                ['name' => 'Procurement'],
                ['name' => 'IT'],
            )
        );

        Schema::dropIfExists('votes');
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger('poll_id')->unsigned();
            $table->foreign('poll_id')->references('id')->on('polls')->onDelete('cascade');
            $table->bigInteger('division_id')->unsigned();
            $table->foreign('division_id')->references('id')->on('divisions')->onDelete('cascade');
            $table->bigInteger('choice_id');
            $table->timestamps();
        });

        Schema::dropIfExists('choices');
        Schema::create('choices', function (Blueprint $table) {
            $table->id();
            $table->string('choice', 191);
            $table->bigInteger('poll_id')->unsigned();
            $table->foreign('poll_id')->references('id')->on('polls')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polls');
        Schema::dropIfExists('divisions');
        Schema::dropIfExists('votes');
        Schema::dropIfExists('choices');
    }
};
