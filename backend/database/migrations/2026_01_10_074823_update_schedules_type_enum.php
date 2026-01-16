<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE schedules MODIFY COLUMN type ENUM(
            'banner_creation',
            'weekly_overview',
            'test_sequence',
            'live_games',
            'multibets',
            'news_section',
            'other'
        ) NOT NULL");
    }

    public function down()
    {
        DB::statement("ALTER TABLE schedules MODIFY COLUMN type ENUM(
            'banner_creation',
            'weekly_overview',
            'live_games',
            'multibets',
            'news_section',
            'other'
        ) NOT NULL");
    }
};