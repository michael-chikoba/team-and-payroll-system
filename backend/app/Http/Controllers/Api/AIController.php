<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AIController extends Controller
{
    protected $apiKey;
    protected $apiUrl;
    protected $model;
    protected $useGroq;

    public function __construct()
    {
        // Prefer Groq over Gemini for better performance
        $this->useGroq = env('USE_GROQ', true);
        
        if ($this->useGroq) {
            $this->apiKey = env('GROQ_API_KEY', '');
            $this->apiUrl = env('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');
            $this->model = env('GROQ_MODEL', 'mixtral-8x7b-32768');
        } else {
            $this->apiKey = env('GEMINI_API_KEY', '');
            $this->apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
            $this->model = 'gemini-pro';
        }
    }

    /**
     * Main chat endpoint with Groq support
     * POST /api/ai/chat
     */
    public function chat(Request $request)
    {
        try {
            $validated = $request->validate([
                'message' => 'required|string|max:2000',
                'context' => 'nullable|array',
                'use_system_knowledge' => 'nullable|boolean',
            ]);

            $userMessage = $validated['message'];
            
            // Check if API key is configured
            if (empty($this->apiKey)) {
                Log::error('AI API key is not configured');
                return response()->json([
                    'response' => $this->getFallbackResponse($userMessage),
                    'success' => false,
                    'error' => 'API key not configured'
                ]);
            }

            $systemContext = $this->getEnhancedSystemContext();
            
            // Check if question is system-related or general
            $isSystemRelated = $this->isSystemRelatedQuestion($userMessage);
            
            // Build conversation context
            $conversationContext = $this->buildConversationContext($validated['context'] ?? []);
            
            $response = null;
            
            if ($this->useGroq) {
                $response = $this->callGroqAPI($systemContext, $conversationContext, $userMessage, $isSystemRelated);
            } else {
                $response = $this->callGeminiAPI($systemContext, $conversationContext, $userMessage);
            }

            if ($response && $response['success']) {
                $this->storeChatHistory($request->user()->id, $userMessage, $response['response']);
                
                Log::info('AI Chat successful', [
                    'user_id' => $request->user()->id,
                    'provider' => $this->useGroq ? 'groq' : 'gemini',
                    'is_system_related' => $isSystemRelated
                ]);
                
                return response()->json([
                    'response' => $response['response'],
                    'success' => true,
                    'provider' => $this->useGroq ? 'groq' : 'gemini',
                    'is_system_related' => $isSystemRelated,
                    'timestamp' => now()->toISOString()
                ]);
            } else {
                // Only use fallback if API call completely failed
                $fallbackResponse = $this->getFallbackResponse($userMessage);
                return response()->json([
                    'response' => $fallbackResponse,
                    'success' => false,
                    'is_fallback' => true
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('AI Chat exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'response' => 'Sorry, I encountered an error. Please try again later.',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Call Groq API with proper formatting
     */
    private function callGroqAPI($systemContext, $conversationContext, $userMessage, $isSystemRelated)
    {
        try {
            // Prepare messages for Groq
            $messages = [
                [
                    'role' => 'system',
                    'content' => $this->getGroqSystemPrompt($systemContext, $isSystemRelated)
                ]
            ];
            
            // Add conversation history
            if (!empty($conversationContext)) {
                $messages = array_merge($messages, $conversationContext);
            }
            
            // Add current user message
            $messages[] = [
                'role' => 'user',
                'content' => $userMessage
            ];
            
            $payload = [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 1000,
                'top_p' => 0.95,
                'stream' => false
            ];
            
            Log::info('Calling Groq API', ['url' => $this->apiUrl, 'model' => $this->model]);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->apiUrl, $payload);
            
            if ($response->successful()) {
                $data = $response->json();
                $aiResponse = $data['choices'][0]['message']['content'] ?? null;
                
                if ($aiResponse) {
                    Log::info('Groq API success', ['response_length' => strlen($aiResponse)]);
                    return ['success' => true, 'response' => $aiResponse];
                } else {
                    Log::error('Groq API invalid response format', ['data' => $data]);
                }
            } else {
                Log::error('Groq API error', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }
            
            return ['success' => false, 'response' => null];
            
        } catch (\Exception $e) {
            Log::error('Groq API exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'response' => null];
        }
    }

    /**
     * Call Gemini API as fallback
     */
    private function callGeminiAPI($systemContext, $conversationContext, $userMessage)
    {
        try {
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $systemContext . "\n\n" . $conversationContext . "User Question: " . $userMessage . "\n\nPlease provide a helpful, professional response:"
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 1000,
                ]
            ];

            $response = Http::withOptions(['verify' => false, 'timeout' => 60])
                ->post($this->apiUrl . '?key=' . $this->apiKey, $payload);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    return [
                        'success' => true,
                        'response' => $data['candidates'][0]['content']['parts'][0]['text']
                    ];
                }
            }
            
            Log::error('Gemini API error', ['response' => $response->body()]);
            return ['success' => false, 'response' => null];
            
        } catch (\Exception $e) {
            Log::error('Gemini API exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'response' => null];
        }
    }

    /**
     * Determine if question is related to the system
     */
    private function isSystemRelatedQuestion($question)
    {
        $systemKeywords = [
            'attendance', 'clock', 'time', 'leave', 'vacation', 'holiday',
            'payroll', 'salary', 'payslip', 'payment', 'deduction', 'tax',
            'employee', 'profile', 'document', 'upload', 'task', 'project',
            'report', 'analytics', 'dashboard', 'notification', 'approval',
            'hr', 'human resources', 'benefit', 'insurance', 'policy',
            'training', 'performance', 'review', 'feedback'
        ];
        
        $lowerQuestion = strtolower($question);
        
        foreach ($systemKeywords as $keyword) {
            if (strpos($lowerQuestion, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get enhanced system context with dual-purpose capability
     */
    private function getEnhancedSystemContext()
    {
        return "You are an AI assistant integrated into an HR and Payroll Management System. 
        You have TWO primary functions:

        **FUNCTION 1: SYSTEM-SPECIFIC ASSISTANCE**
        Help users with specific system features:
        - Attendance tracking (clock in/out, overtime, late arrivals)
        - Leave management (requests, balances, approvals)
        - Payroll (payslips, salary breakdown, deductions)
        - Employee profiles (updates, documents, emergency contacts)
        - Task management (assignments, status updates, work logs)
        - Reporting (generate and download various reports)

        **FUNCTION 2: GENERAL ASSISTANCE**
        For questions NOT related to the HR system:
        - Provide helpful, accurate information on general topics
        - Offer best practices and strategies
        - Suggest resources and approaches
        - Be honest when you don't know something
        - Maintain a professional, helpful tone

        **GUIDELINES:**
        - ALWAYS identify if the question is system-related or general
        - For system questions: Provide specific, step-by-step instructions referencing the actual system features
        - For general questions: Be helpful but concise, suggest best practices
        - If a question could be both, prioritize the system-specific answer first
        - Never share sensitive personal information
        - Redirect to HR for policy-specific questions when appropriate
        - Keep responses clear, actionable, and solution-oriented";
    }

    /**
     * Get Groq-specific system prompt
     */
    private function getGroqSystemPrompt($baseContext, $isSystemRelated)
    {
        if ($isSystemRelated) {
            return $baseContext . "\n\nIMPORTANT: The user is asking about the HR system. Provide specific, actionable instructions referencing actual system features and workflows. Be detailed and step-by-step.";
        } else {
            return "You are a helpful AI assistant integrated into an HR system. 
            The user is asking a general question not specifically about the HR system.
            
            Guidelines for general questions:
            1. Provide helpful, accurate, and detailed information
            2. Suggest best practices and strategies
            3. Be thorough but well-organized
            4. Use bullet points, numbered lists, and clear formatting
            5. Maintain a professional, friendly, and enthusiastic tone
            6. If you don't know something, admit it honestly
            7. For business questions, provide actionable advice
            8. Include examples and real-world applications when relevant
            
            Remember: You're representing a professional HR system, but you're also a knowledgeable AI assistant capable of answering ANY question the user has - from business strategies to personal development, from technical advice to creative ideas.";
        }
    }

    /**
     * Build conversation context from history
     */
    private function buildConversationContext($contextArray)
    {
        if (empty($contextArray)) {
            return [];
        }
        
        $messages = [];
        foreach ($contextArray as $msg) {
            $messages[] = [
                'role' => $msg['role'] === 'user' ? 'user' : 'assistant',
                'content' => $msg['content']
            ];
        }
        
        return $messages;
    }

    /**
     * Get system statistics for AI context (optional - enhances responses)
     */
    private function getSystemStatistics()
    {
        try {
            return [
                'total_employees' => DB::table('employees')->count(),
                'total_departments' => DB::table('employees')->distinct('department')->count('department'),
                'active_leaves' => DB::table('leaves')->where('status', 'pending')->count(),
                'recent_payslips' => DB::table('payslips')->whereMonth('created_at', now()->month)->count(),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get chat history for the authenticated user
     * GET /api/ai/history
     */
    public function getHistory(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $cacheKey = "ai_chat_history_{$userId}";
            
            $history = Cache::get($cacheKey, []);
            
            // Limit to last 50 messages
            $history = array_slice($history, -50);
            
            return response()->json([
                'success' => true,
                'history' => $history,
                'count' => count($history)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get AI history', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'history' => [],
                'message' => 'Failed to retrieve history'
            ], 500);
        }
    }

    /**
     * Clear chat history for the authenticated user
     * DELETE /api/ai/history
     */
    public function clearHistory(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $cacheKey = "ai_chat_history_{$userId}";
            
            Cache::forget($cacheKey);
            
            Log::info('AI chat history cleared', ['user_id' => $userId]);
            
            return response()->json([
                'success' => true,
                'message' => 'Chat history cleared successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clear AI history', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear history'
            ], 500);
        }
    }

    /**
     * Get suggested questions based on user role
     * GET /api/ai/suggestions
     */
    public function getSuggestions(Request $request)
    {
        $user = $request->user();
        $role = $user->role;
        
        $suggestions = [
            'employee' => [
                'How do I request time off?',
                'How do I view my payslip?',
                'How do I clock in and out?',
                'What is my leave balance?',
                'How do I update my profile information?',
                'How do I upload documents?',
                'How do I view my attendance history?',
                'How do I contact my supervisor?',
                'What are the best productivity strategies?',
                'How can I manage work-related stress?'
            ],
            'manager' => [
                'How do I approve leave requests?',
                'How do I view my team\'s attendance?',
                'How do I generate team reports?',
                'How do I assign tasks to my team?',
                'How do I review employee performance?',
                'How do I manage team time-off requests?',
                'What are effective team management strategies?'
            ],
            'admin' => [
                'How do I add new employees?',
                'How do I process payroll?',
                'How do I generate company reports?',
                'How do I manage system settings?',
                'How do I configure tax settings?',
                'How do I manage departments?'
            ]
        ];
        
        $userSuggestions = $suggestions[$role] ?? $suggestions['employee'];
        
        // Randomize and take first 6
        shuffle($userSuggestions);
        $userSuggestions = array_slice($userSuggestions, 0, 8);
        
        return response()->json([
            'success' => true,
            'suggestions' => $userSuggestions,
            'role' => $role
        ]);
    }

    /**
     * Rate a response (feedback for AI improvement)
     * POST /api/ai/rate
     */
    public function rateResponse(Request $request)
    {
        try {
            $validated = $request->validate([
                'response_id' => 'required|string',
                'rating' => 'required|integer|min:1|max:5',
                'feedback' => 'nullable|string|max:500'
            ]);
            
            $userId = $request->user()->id;
            
            // Store rating in database or log
            Log::info('AI Response Rating', [
                'user_id' => $userId,
                'response_id' => $validated['response_id'],
                'rating' => $validated['rating'],
                'feedback' => $validated['feedback'] ?? null
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Thank you for your feedback!'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save rating', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to save rating'
            ], 500);
        }
    }

    /**
     * Store chat history in cache
     */
    private function storeChatHistory($userId, $userMessage, $aiResponse)
    {
        try {
            $cacheKey = "ai_chat_history_{$userId}";
            $history = Cache::get($cacheKey, []);
            
            $history[] = [
                'role' => 'user',
                'content' => $userMessage,
                'timestamp' => now()->toISOString()
            ];
            
            $history[] = [
                'role' => 'assistant',
                'content' => $aiResponse,
                'timestamp' => now()->toISOString()
            ];
            
            // Keep only last 100 messages
            $history = array_slice($history, -100);
            
            Cache::put($cacheKey, $history, now()->addDays(30)); // Store for 30 days
        } catch (\Exception $e) {
            Log::warning('Failed to store chat history', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Fallback response when API fails (returns predefined responses)
     */
    private function getFallbackResponse($question)
    {
        $lowerQuestion = strtolower($question);
        
        // Salon business strategies - specifically added!
        if (strpos($lowerQuestion, 'salon') !== false || 
            (strpos($lowerQuestion, 'business strategies') !== false && strpos($lowerQuestion, 'salon') !== false)) {
            return "**Best Business Strategies for a Salon:**\n\n**1. Customer Retention:**\n• Implement a loyalty program (10th service free)\n• Create membership/subscription models\n• Send automated appointment reminders\n• Follow up after services with personalized offers\n\n**2. Marketing & Growth:**\n• Build strong Instagram presence with before/after galleries\n• Encourage Google Reviews (offer small incentives)\n• Partner with local businesses for cross-promotions\n• Run seasonal promotions (back-to-school, holiday packages)\n\n**3. Operational Efficiency:**\n• Use salon management software for booking/inventory\n• Analyze peak hours and staff accordingly\n• Implement online booking 24/7\n• Train staff on upselling products and services\n\n**4. Financial Management:**\n• Track key metrics: average ticket value, retention rate\n• Implement dynamic pricing for high-demand times\n• Offer package deals to increase cash flow\n• Monitor product cost vs. service pricing\n\n**5. Staff Development:**\n• Invest in continuing education for stylists\n• Create clear career progression paths\n• Implement commission structures that reward performance\n• Regular team meetings for skill sharing\n\nWould you like specific advice on implementing any of these strategies?";
        }
        
        // Attendance related
        if (strpos($lowerQuestion, 'clock in') !== false || strpos($lowerQuestion, 'clock out') !== false) {
            return "**To clock in or out:**\n\n1. Go to the **Attendance** section in the dashboard\n2. Click the **Clock In** button at the start of your shift\n3. Click **Clock Out** at the end of your shift\n4. For overtime, use the **Clock In Overtime** option\n\nYou can also view your attendance history and monthly summaries in the same section.\n\n*Pro tip:* Make sure you're connected to the internet when clocking in/out.";
        }
        
        // Leave related
        if (strpos($lowerQuestion, 'leave') !== false || strpos($lowerQuestion, 'time off') !== false) {
            return "**To request leave:**\n\n1. Navigate to **Leaves → Request Leave**\n2. Select leave type (Annual, Sick, Maternity, etc.)\n3. Specify start and end dates\n4. Add a reason for your leave\n5. Submit for supervisor approval\n\n**To check your leave balance:**\n- Go to **Leaves → Balance**\n- You'll see available days for each leave type\n\n**Note:** Leave requests should be submitted at least 3 days in advance when possible.";
        }
        
        // Payslip related
        if (strpos($lowerQuestion, 'payslip') !== false || strpos($lowerQuestion, 'salary') !== false) {
            return "**To view your payslips:**\n\n1. Go to **Payslips** section in the dashboard\n2. Select the pay period you want to view\n3. Click **View** to see detailed breakdown\n4. Click **Download** to save as PDF\n\n**What's on your payslip?**\n- Base salary and allowances\n- Overtime payments\n- Deductions (tax, loans, advances)\n- Net pay amount\n\nPayslips are typically available by the 5th of each month.";
        }
        
        // Check if it's a general business question
        if (strpos($lowerQuestion, 'business strategy') !== false || 
            strpos($lowerQuestion, 'business tips') !== false ||
            strpos($lowerQuestion, 'how to grow') !== false) {
            return "**General Business Strategy Framework:**\n\n**1. Customer Acquisition:**\n• Identify your target audience clearly\n• Develop a unique value proposition\n• Implement multi-channel marketing\n• Track customer acquisition costs\n\n**2. Customer Retention:**\n• Build loyalty programs\n• Gather and act on feedback\n• Provide exceptional customer service\n• Create community around your brand\n\n**3. Operational Excellence:**\n• Streamline processes\n• Invest in technology\n• Train staff continuously\n• Measure key performance indicators\n\n**4. Financial Health:**\n• Maintain healthy cash flow\n• Diversify revenue streams\n• Control costs without sacrificing quality\n• Plan for seasonality\n\n**5. Growth Strategies:**\n• Expand product/service lines\n• Enter new markets strategically\n• Form strategic partnerships\n• Consider franchising or licensing\n\nWhat specific industry or business area would you like advice on?";
        }
        
        // Default - offer both system and general help
        return "I'm here to help! I can assist with:\n\n**🏢 System-Specific Questions:**\n• 📋 Attendance tracking (clock in/out, overtime)\n• 🏖️ Leave requests and balances\n• 💰 Payslips and salary information\n• ✅ Tasks and assignments\n• 👤 Profile updates and documents\n\n**💡 General Business & Workplace Advice:**\n• 📈 Business strategy and growth\n• ⚡ Productivity and time management\n• 🧘 Stress reduction and wellness\n• 💼 Career development tips\n• 📊 Marketing and customer retention\n\nWhat would you like to know? Just ask me anything - from HR system help to business strategies!";
    }
}