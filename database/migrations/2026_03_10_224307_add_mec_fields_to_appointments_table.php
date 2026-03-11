<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('spid', 10)->nullable()->after('booking_id');
            $table->string('sample_person_name')->nullable()->after('spid');
            $table->string('mobile_number')->nullable()->after('phone');
            $table->string('interviewer_id')->nullable()->after('notes');
            $table->string('supervisor_id')->nullable()->after('interviewer_id');
            $table->enum('visit_stage', ['first_visit', 'second_visit', 'third_visit'])->nullable()->after('supervisor_id');
            $table->unsignedBigInteger('branch_id')->nullable()->after('service_id');
            $table->string('branch_address_snapshot')->nullable()->after('branch_id');
            $table->string('branch_map_link_snapshot')->nullable()->after('branch_address_snapshot');
            $table->unsignedBigInteger('created_by_id')->nullable()->after('user_id');
        });
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreign('branch_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['created_by_id']);
        });
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'spid', 'sample_person_name', 'mobile_number', 'interviewer_id', 'supervisor_id',
                'visit_stage', 'branch_id', 'branch_address_snapshot', 'branch_map_link_snapshot', 'created_by_id'
            ]);
        });
    }
};
