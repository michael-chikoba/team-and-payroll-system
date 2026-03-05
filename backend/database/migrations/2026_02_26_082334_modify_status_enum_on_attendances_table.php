
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("
            ALTER TABLE attendances 
            MODIFY status 
            ENUM('present','absent','late','half_day','completed') 
            NOT NULL DEFAULT 'present'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE attendances 
            MODIFY status 
            ENUM('present','absent','late','half_day') 
            NOT NULL DEFAULT 'present'
        ");
    }
};