<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Agents\GeminiAssistant;
use Illuminate\Support\Str;   // 👈 ADD THIS

class SupportChatController extends Controller
{
    public function index()
    {
        return view('support-chat');
    }

    
public function start()
{
    $conversationId = (string) Str::uuid();

    DB::table('agent_conversations')->insert([
        // 'id' => $conversationId,
        'user_id' => null,            // keep null for now (no auth)
        'title' => 'Support Chat',     // required (NOT NULL)
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
            'conversation_id' => ['required', 'string', 'size:36'],
            'message' => ['required', 'string', 'min:1', 'max:4000'],
            'model' => ['nullable', 'string', 'max:100'],
        ]);

$conversationId = (int) $validated['conversation_id'];
        $userMessage = $validated['message'];
        $model = $validated['model'] ?? null;

        // Save user message
        DB::table('agent_conversation_messages')->insert([
            'conversation_id' => $conversationId,
            'role' => 'user',
            'content' => $userMessage,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Load recent history
        $history = DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->orderByDesc('id')
            ->limit(12)
            ->get()
            ->reverse()
            ->values();

        $historyText = '';
        foreach ($history as $m) {
            $role = strtolower($m->role) === 'assistant' ? 'Assistant' : 'User';
            $historyText .= "{$role}: {$m->content}\n";
        }

        // RAG-lite: fetch relevant knowledge
        $knowledgeText = DB::table('support_knowledge')
            ->where('is_active', true)
            ->where(function ($q) use ($userMessage) {
                $q->where('title', 'like', "%{$userMessage}%")
                  ->orWhere('content', 'like', "%{$userMessage}%");
            })
            ->limit(5)
            ->get()
            ->map(fn ($row) => "Title: {$row->title}\nCategory: {$row->category}\nContent: {$row->content}")
            ->implode("\n\n---\n\n");

        $system = <<<SYS
You are a customer support assistant for our product.
RULES:
- Answer ONLY using the Knowledge Base provided below.
- If the knowledge base does not contain the answer, reply exactly: "I will escalate this to a human support agent."
- Keep responses short, step-by-step, and practical.
SYS;
// dd($system);
        $finalPrompt = $system
            . "\n\nKnowledge Base:\n" . ($knowledgeText ?: "(No relevant knowledge found)")
            . "\n\nConversation:\n" . $historyText
            . "\nAssistant:";

        try {
            $ai = GeminiAssistant::make()->prompt($finalPrompt, model: $model);
            $assistantText = trim($ai->text ?? '');
            dd($ai);

            // Save assistant message
            DB::table('agent_conversation_messages')->insert([
                'conversation_id' => $conversationId,
                'role' => 'assistant',
                'content' => $assistantText,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'conversation_id' => $conversationId,
                'text' => $assistantText,
                'provider' => $ai->meta->provider ?? 'gemini',
                'model' => $ai->meta->model ?? $model,
                'used_kb' => (bool) $knowledgeText,
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