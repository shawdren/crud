<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuditTable extends Migration {

    /**
     * Run the migrations.
     *
     * @access   public
     * @return   void
     */
    public function up()
    {
        if (!Schema::hasTable('_db_audit'))
        {
            Schema::create('_db_audit', function ($table)
                    {
                        $table->increments('id')->unique();
                        $table->string('name', 100);
                        $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                        $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
                    });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @access   public
     * @return   void
     */
    public function down()
    {
        Schema::dropIfExists('_db_audit');
    }

}
