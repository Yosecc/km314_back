<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities_people', function (Blueprint $table) {
            $table->index(['model', 'model_id', 'deleted_at', 'activities_id'], 'activities_people_person_latest_index');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->index(['created_at', 'id'], 'activities_timeline_index');
        });
    }

    public function down(): void
    {
        Schema::table('activities_people', function (Blueprint $table) {
            $table->dropIndex('activities_people_person_latest_index');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex('activities_timeline_index');
        });
    }
};
