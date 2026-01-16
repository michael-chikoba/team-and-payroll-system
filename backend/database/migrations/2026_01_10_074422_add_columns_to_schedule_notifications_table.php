<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // First, let's see what we have
        $columns = Schema::getColumnListing('schedule_notifications');
        
        Schema::table('schedule_notifications', function (Blueprint $table) use ($columns) {
            // Add user_id if missing
            if (!in_array('user_id', $columns)) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }
            
            // Add employee_id if missing
            if (!in_array('employee_id', $columns)) {
                $table->unsignedBigInteger('employee_id')->nullable()->after('user_id');
            }
            
            // Add notification_type if missing
            if (!in_array('notification_type', $columns)) {
                $table->string('notification_type')->after('employee_id');
            }
            
            // Add message if missing
            if (!in_array('message', $columns)) {
                $table->text('message')->after('notification_type');
            }
            
            // Add is_read if missing
            if (!in_array('is_read', $columns)) {
                $table->boolean('is_read')->default(false)->after('message');
            }
            
            // Add read_at if missing
            if (!in_array('read_at', $columns)) {
                $table->timestamp('read_at')->nullable()->after('is_read');
            }
        });
        
        // Add foreign keys separately
        try {
            DB::statement('ALTER TABLE schedule_notifications ADD CONSTRAINT schedule_notifications_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
        } catch (\Exception $e) {
            // Foreign key might already exist
        }
        
        try {
            DB::statement('ALTER TABLE schedule_notifications ADD CONSTRAINT schedule_notifications_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE');
        } catch (\Exception $e) {
            // Foreign key might already exist
        }
        
        // Add indexes
        try {
            DB::statement('CREATE INDEX schedule_notifications_user_id_index ON schedule_notifications(user_id)');
        } catch (\Exception $e) {
            // Index might already exist
        }
        
        try {
            DB::statement('CREATE INDEX schedule_notifications_employee_id_index ON schedule_notifications(employee_id)');
        } catch (\Exception $e) {
            // Index might already exist
        }
        
        try {
            DB::statement('CREATE INDEX schedule_notifications_is_read_index ON schedule_notifications(is_read)');
        } catch (\Exception $e) {
            // Index might already exist
        }
        
        try {
            DB::statement('CREATE INDEX schedule_notifications_created_at_index ON schedule_notifications(created_at)');
        } catch (\Exception $e) {
            // Index might already exist
        }
    }

    public function down()
    {
        Schema::table('schedule_notifications', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['user_id']);
            $table->dropIndex(['employee_id']);
            $table->dropIndex(['is_read']);
            $table->dropIndex(['created_at']);
            
            // Drop foreign keys
            $table->dropForeign(['user_id']);
            $table->dropForeign(['employee_id']);
            
            // Drop columns
            $table->dropColumn([
                'user_id',
                'employee_id',
                'notification_type',
                'message',
                'is_read',
                'read_at'
            ]);
        });
    }
};