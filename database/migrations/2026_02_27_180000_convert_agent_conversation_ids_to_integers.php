<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('agent_conversations') || !Schema::hasTable('agent_conversation_messages')) {
            return;
        }

        if (
            $this->columnIsInteger('agent_conversations', 'id') &&
            $this->columnIsInteger('agent_conversation_messages', 'conversation_id')
        ) {
            return;
        }

        $conversationBackupTable = 'agent_conversations_uuid_backup';
        $messageBackupTable = 'agent_conversation_messages_uuid_backup';

        if (Schema::hasTable($conversationBackupTable) || Schema::hasTable($messageBackupTable)) {
            throw new RuntimeException('Backup tables already exist. Please clean up old conversion backups first.');
        }

        Schema::disableForeignKeyConstraints();

        Schema::rename('agent_conversations', $conversationBackupTable);
        Schema::rename('agent_conversation_messages', $messageBackupTable);

        $this->createIntegerTables();
        $this->migrateUuidDataToInteger(
            conversationBackupTable: $conversationBackupTable,
            messageBackupTable: $messageBackupTable,
        );

        Schema::drop($messageBackupTable);
        Schema::drop($conversationBackupTable);

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        if (!Schema::hasTable('agent_conversations') || !Schema::hasTable('agent_conversation_messages')) {
            return;
        }

        if (
            !$this->columnIsInteger('agent_conversations', 'id') &&
            !$this->columnIsInteger('agent_conversation_messages', 'conversation_id')
        ) {
            return;
        }

        $conversationBackupTable = 'agent_conversations_int_backup';
        $messageBackupTable = 'agent_conversation_messages_int_backup';

        if (Schema::hasTable($conversationBackupTable) || Schema::hasTable($messageBackupTable)) {
            throw new RuntimeException('Backup tables already exist. Please clean up old conversion backups first.');
        }

        Schema::disableForeignKeyConstraints();

        Schema::rename('agent_conversations', $conversationBackupTable);
        Schema::rename('agent_conversation_messages', $messageBackupTable);

        $this->createUuidTables();
        $this->migrateIntegerDataToUuid(
            conversationBackupTable: $conversationBackupTable,
            messageBackupTable: $messageBackupTable,
        );

        Schema::drop($messageBackupTable);
        Schema::drop($conversationBackupTable);

        Schema::enableForeignKeyConstraints();
    }

    private function createIntegerTables(): void
    {
        Schema::create('agent_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('title');
            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
        });

        Schema::create('agent_conversation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('agent_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable();
            $table->string('agent')->nullable();
            $table->string('role', 25);
            $table->text('content');
            $table->text('attachments')->nullable();
            $table->text('tool_calls')->nullable();
            $table->text('tool_results')->nullable();
            $table->text('usage')->nullable();
            $table->text('meta')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'user_id', 'updated_at'], 'conversation_index');
            $table->index(['user_id']);
        });
    }

    private function createUuidTables(): void
    {
        Schema::create('agent_conversations', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->foreignId('user_id')->nullable();
            $table->string('title');
            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
        });

        Schema::create('agent_conversation_messages', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('conversation_id', 36)->index();
            $table->foreignId('user_id')->nullable();
            $table->string('agent');
            $table->string('role', 25);
            $table->text('content');
            $table->text('attachments');
            $table->text('tool_calls');
            $table->text('tool_results');
            $table->text('usage');
            $table->text('meta');
            $table->timestamps();

            $table->index(['conversation_id', 'user_id', 'updated_at'], 'conversation_index');
            $table->index(['user_id']);
        });
    }

    private function migrateUuidDataToInteger(string $conversationBackupTable, string $messageBackupTable): void
    {
        $conversationIdMap = [];

        $conversations = DB::table($conversationBackupTable)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($conversations as $conversation) {
            $newConversationId = (int) DB::table('agent_conversations')->insertGetId([
                'user_id' => $conversation->user_id,
                'title' => $conversation->title,
                'created_at' => $conversation->created_at,
                'updated_at' => $conversation->updated_at,
            ]);

            $conversationIdMap[(string) $conversation->id] = $newConversationId;
        }

        $messages = DB::table($messageBackupTable)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($messages as $message) {
            $newConversationId = $conversationIdMap[(string) $message->conversation_id] ?? null;

            if ($newConversationId === null) {
                continue;
            }

            DB::table('agent_conversation_messages')->insert([
                'conversation_id' => $newConversationId,
                'user_id' => $message->user_id,
                'agent' => $message->agent,
                'role' => $message->role,
                'content' => $message->content,
                'attachments' => $message->attachments,
                'tool_calls' => $message->tool_calls,
                'tool_results' => $message->tool_results,
                'usage' => $message->usage,
                'meta' => $message->meta,
                'created_at' => $message->created_at,
                'updated_at' => $message->updated_at,
            ]);
        }
    }

    private function migrateIntegerDataToUuid(string $conversationBackupTable, string $messageBackupTable): void
    {
        $conversationIdMap = [];

        $conversations = DB::table($conversationBackupTable)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($conversations as $conversation) {
            $newConversationId = (string) Str::uuid();

            DB::table('agent_conversations')->insert([
                'id' => $newConversationId,
                'user_id' => $conversation->user_id,
                'title' => $conversation->title,
                'created_at' => $conversation->created_at,
                'updated_at' => $conversation->updated_at,
            ]);

            $conversationIdMap[(int) $conversation->id] = $newConversationId;
        }

        $messages = DB::table($messageBackupTable)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($messages as $message) {
            $newConversationId = $conversationIdMap[(int) $message->conversation_id] ?? null;

            if ($newConversationId === null) {
                continue;
            }

            DB::table('agent_conversation_messages')->insert([
                'id' => (string) Str::uuid(),
                'conversation_id' => $newConversationId,
                'user_id' => $message->user_id,
                'agent' => $message->agent ?? '',
                'role' => $message->role,
                'content' => $message->content,
                'attachments' => $message->attachments ?? '[]',
                'tool_calls' => $message->tool_calls ?? '[]',
                'tool_results' => $message->tool_results ?? '[]',
                'usage' => $message->usage ?? '[]',
                'meta' => $message->meta ?? '[]',
                'created_at' => $message->created_at,
                'updated_at' => $message->updated_at,
            ]);
        }
    }

    private function columnIsInteger(string $table, string $column): bool
    {
        if (!Schema::hasColumn($table, $column)) {
            return false;
        }

        $columnType = strtolower(Schema::getColumnType($table, $column));

        return str_contains($columnType, 'int');
    }
};
