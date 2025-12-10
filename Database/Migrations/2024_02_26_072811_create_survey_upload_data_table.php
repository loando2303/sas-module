<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSurveyUploadDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('survey_upload_data', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('manifest_id')->nullable();
            $table->integer('survey_id')->nullable();
            $table->tinyInteger('status')->nullable();
            $table->mediumText('data')->nullable();
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
        Schema::dropIfExists('survey_upload_data');
    }
}
