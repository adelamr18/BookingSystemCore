<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('appointments')) {
            DB::table('appointments')
                ->where('status', 'Pending payment')
                ->update(['status' => 'Pending']);

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE appointments MODIFY COLUMN status ENUM('Pending','Processing','Confirmed','Cancelled','Completed','On Hold','Rescheduled','No Show') DEFAULT 'Confirmed'");
            }

            $hasAppointmentEmail = Schema::hasColumn('appointments', 'email');
            $hasAppointmentAmount = Schema::hasColumn('appointments', 'amount');

            Schema::table('appointments', function (Blueprint $table) use ($hasAppointmentEmail, $hasAppointmentAmount) {
                if ($hasAppointmentEmail) {
                    $table->dropColumn('email');
                }

                if ($hasAppointmentAmount) {
                    $table->dropColumn('amount');
                }
            });
        }

        if (Schema::hasTable('services')) {
            $hasServicePrice = Schema::hasColumn('services', 'price');
            $hasServiceSalePrice = Schema::hasColumn('services', 'sale_price');

            Schema::table('services', function (Blueprint $table) use ($hasServicePrice, $hasServiceSalePrice) {
                if ($hasServicePrice) {
                    $table->dropColumn('price');
                }

                if ($hasServiceSalePrice) {
                    $table->dropColumn('sale_price');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('services')) {
            $hasServicePrice = Schema::hasColumn('services', 'price');
            $hasServiceSalePrice = Schema::hasColumn('services', 'sale_price');

            Schema::table('services', function (Blueprint $table) use ($hasServicePrice, $hasServiceSalePrice) {
                if (!$hasServicePrice) {
                    $table->decimal('price', 8, 2)->default(0)->after('meta_keyword');
                }

                if (!$hasServiceSalePrice) {
                    $table->decimal('sale_price', 8, 2)->nullable()->after('price');
                }
            });
        }

        if (Schema::hasTable('appointments')) {
            $hasAppointmentEmail = Schema::hasColumn('appointments', 'email');
            $hasAppointmentAmount = Schema::hasColumn('appointments', 'amount');

            Schema::table('appointments', function (Blueprint $table) use ($hasAppointmentEmail, $hasAppointmentAmount) {
                if (!$hasAppointmentEmail) {
                    $table->string('email')->nullable()->after('name');
                }

                if (!$hasAppointmentAmount) {
                    $table->decimal('amount', 8, 2)->nullable()->after('notes');
                }
            });

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE appointments MODIFY COLUMN status ENUM('Pending','Pending payment','Processing','Confirmed','Cancelled','Completed','On Hold','Rescheduled','No Show') DEFAULT 'Confirmed'");
            }
        }
    }
};
