<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'requests',
            function (Blueprint $table) {
                $table->id();
                $table->foreignId('work_id')->constrained('works')->onDelete('cascade');
                $table->foreignId('manager_id')->constrained('managers')->onDelete('cascade');
                $table->boolean('approved');
                $table->text('staff_remarks');
                $table->text('admin_remarks');
                $table->timestamps();
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
