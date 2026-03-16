<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Agents\GeminiAssistant;
use App\Services\Ai\ProviderFailoverService;

class SupportChatController extends Controller
{
    public function __construct(
        protected ProviderFailoverService $providerFailoverService
    ) {
    }

    public function index()
    {
        return view('support-chat');
    }

    public function start()
    {
        $conversationId = (int) DB::table('agent_conversations')->insertGetId([
            'user_id' => null,
            'title' => 'Support Chat',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'conversation_id' => $conversationId,
        ]);
    }

    public function send(Request $request)
    {
        $requestStartedAt = microtime(true);

        $validated = $request->validate([
            'conversation_id' => ['required', 'integer', 'min:1'],
            'message' => ['required', 'string', 'min:1', 'max:4000'],
            'model' => ['nullable', 'string', 'max:100'],
        ]);

        $conversationId = (int) $validated['conversation_id'];
        $userMessage = $validated['message'];
        $model = $validated['model'] ?? null;

        $existsStartedAt = microtime(true);
        $conversationExists = DB::table('agent_conversations')
            ->where('id', $conversationId)
            ->exists();
        $conversationExistsMs = (int) round((microtime(true) - $existsStartedAt) * 1000);

        if (!$conversationExists) {
            return response()->json([
                'error' => 'ConversationNotFound',
                'message' => 'Invalid conversation_id',
            ], 404);
        }

        try {
            $now = now();

            $insertUserStartedAt = microtime(true);
            DB::table('agent_conversation_messages')->insert([
                'conversation_id' => $conversationId,
                'user_id' => null,
                'agent' => null,
                'role' => 'user',
                'content' => $userMessage,
                'attachments' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $insertUserMs = (int) round((microtime(true) - $insertUserStartedAt) * 1000);

            $historyStartedAt = microtime(true);
            $history = DB::table('agent_conversation_messages')
                ->select('role', 'content')
                ->where('conversation_id', $conversationId)
                ->orderByDesc('created_at')
                ->limit(12)
                ->get()
                ->reverse()
                ->values();
            $historyMs = (int) round((microtime(true) - $historyStartedAt) * 1000);

            $historyText = '';
            foreach ($history as $m) {
                $roleLabel = strtolower($m->role) === 'assistant' ? 'Assistant' : 'User';
                $historyText .= "{$roleLabel}: {$m->content}\n";
            }

            $kbStartedAt = microtime(true);
            $tokens = collect(preg_split('/\W+/u', mb_strtolower($userMessage)))
                ->filter(fn ($t) => mb_strlen($t) >= 3)
                ->unique()
                ->take(8)
                ->values();

            $kbRows = collect();
            if ($tokens->isNotEmpty()) {
                $kbRows = DB::table('support_knowledge')
                    ->select('title', 'category', 'content')
                    ->where('is_active', 1)
                    ->where(function ($q) use ($tokens) {
                        foreach ($tokens as $t) {
                            $q->orWhere('title', 'like', "%{$t}%")
                                ->orWhere('content', 'like', "%{$t}%");
                        }
                    })
                    ->limit(5)
                    ->get();
            }
            $kbMs = (int) round((microtime(true) - $kbStartedAt) * 1000);

            $knowledgeText = $kbRows
                ->map(fn ($row) => "Title: {$row->title}\nCategory: {$row->category}\nContent: {$row->content}")
                ->implode("\n\n---\n\n");

            $system = <<<SYS
You are the customer support assistant for our product.

Behavior rules:
- Be polite and professional. If the user greets you, greet them back briefly and politely.
- Use ONLY the information provided in the Knowledge Base below to answer product-related questions.
- If the user’s question is not answered by the Knowledge Base, reply with exactly:
"I will escalate this to a human support agent."
- Keep responses short, step-by-step, and practical. Do not add guesses, assumptions, or extra facts.

You will receive:
1) Knowledge Base (authoritative facts)
2) Conversation context
Respond as the Assistant.
SYS;

            $finalPrompt = $system
                . "\n\nKnowledge Base:\n" . ($knowledgeText ?: "(No relevant knowledge found)")
                . "\n\nConversation:\n" . $historyText
                . "\nAssistant:";

            $providerChain = $this->providerFailoverService->chain(
                'ai.support_chat.provider_failover',
                $model
            );

            $aiStartedAt = microtime(true);
            $ai = GeminiAssistant::make()->prompt($finalPrompt, provider: $providerChain);
            $assistantText = trim($ai->text ?? '');
            $assistantProvider = $ai->meta->provider ?? (array_key_first($providerChain) ?: 'gemini');
            $aiMs = (int) round((microtime(true) - $aiStartedAt) * 1000);

            $persistStartedAt = microtime(true);
            DB::transaction(function () use ($conversationId, $assistantText, $assistantProvider, $now) {
                DB::table('agent_conversation_messages')->insert([
                    'conversation_id' => $conversationId,
                    'user_id' => null,
                    'agent' => $assistantProvider,
                    'role' => 'assistant',
                    'content' => $assistantText,
                    'attachments' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('agent_conversations')
                    ->where('id', $conversationId)
                    ->update(['updated_at' => $now]);
            });
            $persistMs = (int) round((microtime(true) - $persistStartedAt) * 1000);

            Log::info('SUPPORT CHAT TIMING', [
                'conversation_id' => $conversationId,
                'exists_ms' => $conversationExistsMs,
                'insert_user_ms' => $insertUserMs,
                'history_ms' => $historyMs,
                'kb_ms' => $kbMs,
                'ai_ms' => $aiMs,
                'persist_ms' => $persistMs,
                'total_ms' => (int) round((microtime(true) - $requestStartedAt) * 1000),
                'history_count' => $history->count(),
                'kb_matches' => $kbRows->count(),
                'tokens_count' => $tokens->count(),
                'providers_attempted' => array_keys($providerChain),
                'provider' => $assistantProvider,
                'model' => $ai->meta->model ?? ($model ?: 'default'),
            ]);

            return response()->json([
                'conversation_id' => $conversationId,
                'text' => $assistantText,
                'provider' => $assistantProvider,
                'model' => $ai->meta->model ?? ($model ?: 'default'),
                'used_kb' => $kbRows->count() > 0,
                'kb_matches' => $kbRows->count(),
            ]);
        } catch (\Throwable $e) {
            Log::error('SUPPORT CHAT ERROR', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return response()->json([
                'error' => get_class($e),
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}