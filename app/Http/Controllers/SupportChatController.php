<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Agents\GeminiAssistant;

class SupportChatController extends Controller
{
    public function index()
    {
        return view('support-chat');
    }

    public function start()
    {
        $conversationId = (int) DB::table('agent_conversations')->insertGetId([
            'user_id' => null,        // keep null for now (no auth)
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
        $validated = $request->validate([
            'conversation_id' => ['required', 'integer', 'min:1'],
            'message' => ['required', 'string', 'min:1', 'max:4000'],
            'model' => ['nullable', 'string', 'max:100'],
        ]);

        $conversationId = (int) $validated['conversation_id'];
        $userMessage = $validated['message'];
        $model = $validated['model'] ?? null;

        // Optional safety: check conversation exists
        $conversationExists = DB::table('agent_conversations')
            ->where('id', $conversationId)
            ->exists();

        if (!$conversationExists) {
            return response()->json([
                'error' => 'ConversationNotFound',
                'message' => 'Invalid conversation_id',
            ], 404);
        }

        try {
            return DB::transaction(function () use ($conversationId, $userMessage, $model) {
                $now = now();

                // Save USER message.
                DB::table('agent_conversation_messages')->insert([
                    'conversation_id' => $conversationId,
                    'user_id' => null,               // or auth()->id()
                    'agent' => null,                 // can keep null for user
                    'role' => 'user',
                    'content' => $userMessage,
                    'attachments' => null,           // or '[]' if you want JSON style
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // Load last 12 messages for context (order by created_at, NOT id).
                $history = DB::table('agent_conversation_messages')
                    ->where('conversation_id', $conversationId)
                    ->orderByDesc('created_at')
                    ->limit(12)
                    ->get()
                    ->reverse()
                    ->values();

                $historyText = '';
                foreach ($history as $m) {
                    $roleLabel = strtolower($m->role) === 'assistant' ? 'Assistant' : 'User';
                    $historyText .= "{$roleLabel}: {$m->content}\n";
                }

                // RAG-lite v2: token-based KB matching.
                $tokens = collect(preg_split('/\W+/u', mb_strtolower($userMessage)))
                    ->filter(fn ($t) => mb_strlen($t) >= 3)
                    ->unique()
                    ->values();

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

                $knowledgeText = $kbRows
                    ->map(fn ($row) => "Title: {$row->title}\nCategory: {$row->category}\nContent: {$row->content}")
                    ->implode("\n\n---\n\n");

                $system = <<<SYS
You are a customer support assistant for our product.
RULES:
- Answer ONLY using the Knowledge Base provided below.
- if any word is related to our knowledge base then answer accordingly.
- answer greting related statements from your side with most polite way.
- If the knowledge base does not contain the answer, reply exactly: "I will escalate this to a human support agent."
- Keep responses short, step-by-step, and practical.
SYS;

                $finalPrompt = $system
                    . "\n\nKnowledge Base:\n" . ($knowledgeText ?: "(No relevant knowledge found)")
                    . "\n\nConversation:\n" . $historyText
                    . "\nAssistant:";

                // Call Gemini.
                $ai = GeminiAssistant::make()->prompt($finalPrompt, model: $model);
                $assistantText = trim($ai->text ?? '');

                // Save ASSISTANT message.
                DB::table('agent_conversation_messages')->insert([
                    'conversation_id' => $conversationId,
                    'user_id' => null,
                    'agent' => 'gemini',             // store provider/agent name here
                    'role' => 'assistant',
                    'content' => $assistantText,
                    'attachments' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // Optional: bump conversation updated_at.
                DB::table('agent_conversations')
                    ->where('id', $conversationId)
                    ->update(['updated_at' => $now]);

                return response()->json([
                    'conversation_id' => $conversationId,
                    'text' => $assistantText,
                    'provider' => $ai->meta->provider ?? 'gemini',
                    'model' => $ai->meta->model ?? ($model ?? 'gemini-3-flash-preview'),
                    'used_kb' => $kbRows->count() > 0,
                    'kb_matches' => $kbRows->count(),
                ]);
            });
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
