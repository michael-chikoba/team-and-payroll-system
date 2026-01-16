<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('linked_task_id')->constrained('tasks')->onDelete('cascade');
            $table->enum('link_type', [
                'blocks',
                'blocked_by',
                'relates_to',
                'duplicates',
                'duplicated_by',
                'parent_of',
                'child_of'
            ]);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            // Prevent duplicate links
            $table->unique(['task_id', 'linked_task_id', 'link_type']);
            $table->index(['task_id', 'link_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_links');
    }
};