<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {        
        Schema::create('looks', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('description')->nullable();
            $table->string('slug')->nullable();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('group_id')->default(1);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('purchases')->default(0);
            $table->string('products')->nullable();          
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('looks');
    }
}
