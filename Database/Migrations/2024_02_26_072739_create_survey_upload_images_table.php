<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSurveyUploadImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('survey_upload_images', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('manifest_id')->nullable();
            $table->integer('survey_id')->nullable();
            $table->string('reference', 255)->nullable();
            $table->string('image_type', 255)->nullable();
            $table->string('file_name', 500)->nullable();
            $table->string('path', 500)->nullable();
            $table->string('mime', 255)->nullable();
            $table->integer('size')->nullable();
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
        Schema::dropIfExists('survey_upload_images');
    }
}
