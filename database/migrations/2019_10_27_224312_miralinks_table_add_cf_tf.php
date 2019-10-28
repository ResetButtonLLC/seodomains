<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MiralinksTableAddCfTf extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('miralinks', function (Blueprint $table) {
            $table->integer('majestic_tf')->after('desc')->nullable();
            $table->integer('majestic_cf')->after('majestic_tf')->nullable();
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->timestamp('majestic_updated')->nullable();
        });

        //Не может использоваться, т.к. значение TF/CF может не изменится и триггер не сработает, т.к. не может быть направлен на колонку.
        //Оставлю как пример
        //
        //DELIMITER //
        //DROP TRIGGER IF EXISTS `majestic_timestamp_tf`;
        //CREATE TRIGGER `majestic_timestamp_tf` BEFORE UPDATE ON `domains`
        //	FOR EACH ROW
        //  BEGIN
        //		SET NEW.majestic_updated = NOW();
        //  END//
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('miralinks', function (Blueprint $table) {
            $table->dropColumn('majestic_tf');
            $table->dropColumn('majestic_cf');
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn('majestic_updated');
        });
    }
}
