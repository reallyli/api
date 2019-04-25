<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBusinessWorkingHoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_working_hours', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedTinyInteger('day')->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses');

            /*
             * Days:
             * 0 = Sun
             * 1 = Mon
             * 2 = Tues
             * 3 = Wed
             * 4 = Thurs
             * 5 = Fri
             * 6 = Sat
             * */
        });

        //Schema::dropIfExists('business_hours');
        //Schema::dropIfExists('business_opening_hours');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_working_hours');
    }
}
