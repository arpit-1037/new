<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agent_conversation_messages')) {
            Schema::table('agent_conversation_messages', function (Blueprint $table) {
                $table->index(['conversation_id', 'created_at'], 'conversation_created_at_index');
            });
        }

        if (Schema::hasTable('support_knowledge')) {
            Schema::table('support_knowledge', function (Blueprint $table) {
                $table->index(['is_active'], 'support_knowledge_is_active_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('agent_conversation_messages')) {
            Schema::table('agent_conversation_messages', function (Blueprint $table) {
                $table->dropIndex('conversation_created_at_index');
            });
        }

        if (Schema::hasTable('support_knowledge')) {
            Schema::table('support_knowledge', function (Blueprint $table) {
                $table->dropIndex('support_knowledge_is_active_index');
            });
        }
    }
};
