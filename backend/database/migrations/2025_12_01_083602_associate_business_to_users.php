<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, check if business_id column exists in users table
        if (!Schema::hasColumn('users', 'business_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('business_id')->nullable()->after('id');
                $table->foreign('business_id')
                      ->references('id')
                      ->on('businesses')
                      ->onDelete('set null');
            });
        }

        // Associate existing users with business ID 3
        // Verify business ID 3 exists before assignment
        
        $targetBusiness = DB::table('businesses')->where('id', 3)->first();
        
        if ($targetBusiness) {
            // Update users who don't have a business_id assigned
            DB::table('users')
                ->whereNull('business_id')
                ->update(['business_id' => 3]);
                
            Log::info("Users associated with business ID 3: {$targetBusiness->name}");
        } else {
            Log::warning("Business ID 3 not found. No users were updated.");
        }

        // Alternative: If you have a specific business assignment logic
        // For example, if you have a business_user pivot table:
        /*
        $businessUsers = DB::table('business_user')->get();
        
        foreach ($businessUsers as $bu) {
            DB::table('users')
                ->where('id', $bu->user_id)
                ->whereNull('business_id')
                ->update(['business_id' => $bu->business_id]);
        }
        */

        // Log the associations for verification
        $associatedUsers = DB::table('users')
            ->whereNotNull('business_id')
            ->count();
        
        Log::info("Associated {$associatedUsers} users with businesses");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'business_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['business_id']);
                $table->dropColumn('business_id');
            });
        }
    }
};