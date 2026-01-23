<?php
// database/migrations/xxxx_xx_xx_create_ticket_types_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->default('#3B82F6');
            $table->json('workflow')->nullable(); // Custom workflow for this type
            $table->json('fields')->nullable(); // Custom form fields
            $table->json('categories')->nullable(); // Available categories for this type
            $table->json('subcategories')->nullable(); // Available subcategories
            $table->integer('sla_hours')->default(72); // Default SLA in hours
            $table->boolean('requires_approval')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Insert default ticket types
        DB::table('ticket_types')->insert([
            [
                'name' => 'Issue',
                'slug' => 'issue',
                'description' => 'Report problems, bugs, or system errors',
                'icon' => 'exclamation-circle',
                'color' => '#EF4444',
                'categories' => json_encode(['Technical', 'System', 'Access', 'Performance', 'Other']),
                'subcategories' => json_encode(['Login', 'Slow Performance', 'Error Message', 'Data Issue', 'Bug']),
                'sla_hours' => 24,
                'requires_approval' => false,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Request',
                'slug' => 'request',
                'description' => 'Request for new features, access, or information',
                'icon' => 'document-text',
                'color' => '#10B981',
                'categories' => json_encode(['Access', 'Hardware', 'Software', 'Data', 'Training']),
                'subcategories' => json_encode(['New Account', 'Permission', 'Report', 'Equipment', 'Consultation']),
                'sla_hours' => 48,
                'requires_approval' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Change Request',
                'slug' => 'change_request',
                'description' => 'Request for system or process changes',
                'icon' => 'adjustments',
                'color' => '#8B5CF6',
                'categories' => json_encode(['Process', 'System', 'Policy', 'Configuration', 'Integration']),
                'subcategories' => json_encode(['Enhancement', 'Modification', 'Deployment', 'Integration', 'Customization']),
                'sla_hours' => 72,
                'requires_approval' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('ticket_types');
    }
};