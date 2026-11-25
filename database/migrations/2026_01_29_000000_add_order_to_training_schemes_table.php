<?php

use App\Models\TrainingScheme;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('training_schemes', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('name');
        });

        // Initialize existing records order by created_at
        if (Schema::hasTable('training_schemes')) {
            $schemes = TrainingScheme::orderBy('created_at')->get();
            $i = 0;
            foreach ($schemes as $s) {
                $s->order = $i++;
                $s->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_schemes', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
