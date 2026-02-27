<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_knowledge', function (Blueprint $table) {
            $table->id();
            $table->string('title');                 // e.g., "Leak Sensor Offline"
            $table->string('category');              // e.g., product/pricing/troubleshooting/policy
            $table->text('content');                 // the actual knowledge
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_knowledge');
    }
};