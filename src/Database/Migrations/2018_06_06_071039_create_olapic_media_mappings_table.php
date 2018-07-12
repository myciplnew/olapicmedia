<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOlapicMediaMappingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('olapic_media_mappings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('media_id')->default(0);
            $table->unsignedInteger('olapic_media_id')->default(0);
            $table->enum('type',['upload', 'download']);
            $table->enum('status',['new', 'inprogress','success','error']);
            $table->string('message')->nullable();
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
        Schema::dropIfExists('olapic_media_mappings');
    }
}
