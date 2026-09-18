<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 8's InvoiceNumberGenerator wrote raw strings (e.g. "0") into
        // this column, which aren't themselves valid JSON. Re-encode any
        // pre-existing row before changing the column type so the ALTER
        // doesn't fail against real data.
        DB::table('settings')->get()->each(function ($row) {
            json_decode((string) $row->value);

            if (json_last_error() !== JSON_ERROR_NONE) {
                DB::table('settings')->where('id', $row->id)->update([
                    'value' => json_encode($row->value),
                ]);
            }
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->json('value')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->text('value')->nullable()->change();
        });
    }
};
