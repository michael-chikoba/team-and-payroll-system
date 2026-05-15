<?php
// app/Http/Controllers/Api/LearningController.php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\LearningCourse;
use App\Models\LearningModule;
use App\Models\LearningEnrollment;
use App\Models\LearningModuleProgress;
use App\Models\LearningAssessment;
use App\Models\LearningAttempt;
use App\Models\LearningQuestion;
use App\Models\LearningQuestionOption;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LearningController extends Controller
{
    // ── COURSES ──────────────────────────────────────
    
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $businessId = $user->business_id;
            
            if (!$businessId) {
                $employee = Employee::where('user_id', $user->id)->first();
                if ($employee && $employee->business_id) {
                    $businessId = $employee->business_id;
                }
            }
            
            if (!$businessId) {
                return response()->json([
                    'success' => true,
                    'data' => ['data' => [], 'current_page' => 1, 'per_page' => 15, 'total' => 0],
                    'message' => 'No business assigned to user'
                ]);
            }
            
            $query = LearningCourse::where('business_id', $businessId)
                ->where('status', 'published')
                ->with(['modules', 'assessment', 'createdBy'])
                ->withCount('modules');
            
            // Filter by role (admin sees all, employees see assigned/enrolled)
            if ($user->role !== 'admin' && $user->role !== 'manager') {
                $employee = Employee::where('user_id', $user->id)->first();
                if ($employee) {
                    $query->where(function($q) use ($employee) {
                        $q->where('allow_self_enroll', true)
                          ->orWhereHas('enrollments', function($eq) use ($employee) {
                              $eq->where('employee_id', $employee->id);
                          });
                          
                        if ($employee->department_id) {
                            $q->orWhereJsonContains('assigned_departments', $employee->department_id);
                        }
                        
                        if ($employee->role) {
                            $q->orWhereJsonContains('assigned_roles', $employee->role);
                        }
                    });
                }
            }
            
            $courses = $query->latest()->paginate($request->get('per_page', 15));
            
            // Add enrollment status for each course
            if ($employee = Employee::where('user_id', $user->id)->first()) {
                foreach ($courses as $course) {
                    $enrollment = LearningEnrollment::where('course_id', $course->id)
                        ->where('employee_id', $employee->id)
                        ->first();
                    if ($enrollment) {
                        $course->is_enrolled = true;
                        $course->enrollment_status = $enrollment->status;
                        $course->progress_percent = $enrollment->progress_percent;
                    } else {
                        $course->is_enrolled = false;
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $courses
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch courses: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch courses',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'nullable|string|max:100',
                'assigned_departments' => 'nullable|array',
                'assigned_roles' => 'nullable|array',
                'allow_self_enroll' => 'boolean',
                'estimated_minutes' => 'nullable|integer|min:1'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $businessId = $user->business_id;
            
            if (!$businessId) {
                $employee = Employee::where('user_id', $user->id)->first();
                if ($employee && $employee->business_id) {
                    $businessId = $employee->business_id;
                }
            }
            
            if (!$businessId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not have a business assigned. Please contact administrator.'
                ], 400);
            }
            
            $course = LearningCourse::create([
                'business_id' => $businessId,
                'created_by' => $user->id,
                'title' => $request->title,
                'description' => $request->description,
                'category' => $request->category ?? 'General',
                'assigned_departments' => $request->assigned_departments,
                'assigned_roles' => $request->assigned_roles,
                'allow_self_enroll' => $request->allow_self_enroll ?? true,
                'estimated_minutes' => $request->estimated_minutes,
                'status' => 'published'
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Course created successfully',
                'data' => $course
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create course: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create course: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $course = LearningCourse::with(['modules', 'assessment', 'createdBy'])
                ->findOrFail($id);
            
            $user = Auth::user();
            $employee = Employee::where('user_id', $user->id)->first();
            $enrollment = null;
            $completedModuleIds = [];
            
            if ($employee) {
                $enrollment = LearningEnrollment::where('course_id', $id)
                    ->where('employee_id', $employee->id)
                    ->first();
                    
                if ($enrollment) {
                    $completedModules = LearningModuleProgress::where('enrollment_id', $enrollment->id)
                        ->where('completed', true)
                        ->get();
                    $completedModuleIds = $completedModules->pluck('module_id')->toArray();
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $course,
                'is_enrolled' => !is_null($enrollment),
                'enrollment' => $enrollment,
                'completed_modules' => $completedModuleIds,
                'total_modules' => $course->modules->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch course: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Course not found'
            ], 404);
        }
    }
    
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $course = LearningCourse::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'category' => 'nullable|string|max:100',
                'assigned_departments' => 'nullable|array',
                'assigned_roles' => 'nullable|array',
                'allow_self_enroll' => 'boolean',
                'estimated_minutes' => 'nullable|integer|min:1'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $course->update($request->only([
                'title', 'description', 'category', 'assigned_departments', 
                'assigned_roles', 'allow_self_enroll', 'estimated_minutes'
            ]));
            
            return response()->json([
                'success' => true,
                'message' => 'Course updated successfully',
                'data' => $course
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update course: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update course'
            ], 500);
        }
    }
    
    public function destroy($id): JsonResponse
    {
        try {
            $course = LearningCourse::findOrFail($id);
            $course->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Course deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete course: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete course'
            ], 500);
        }
    }

    // ── MODULES ──────────────────────────────────────
    
    public function storeModule(Request $request, $courseId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'required|in:video,pdf,text,link,quiz,scorm',
                'content' => 'required|string',
                'duration_minutes' => 'nullable|integer|min:1',
                'order' => 'nullable|integer'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $course = LearningCourse::findOrFail($courseId);
            
            $maxOrder = LearningModule::where('course_id', $courseId)->max('order');
            $module = LearningModule::create([
                'course_id' => $courseId,
                'title' => $request->title,
                'description' => $request->description,
                'type' => $request->type,
                'content' => $request->content,
                'duration_minutes' => $request->duration_minutes,
                'order' => $request->order ?? ($maxOrder + 1)
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Module created successfully',
                'data' => $module
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create module: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create module'
            ], 500);
        }
    }
    
    public function updateModule(Request $request, $courseId, $moduleId): JsonResponse
    {
        try {
            $module = LearningModule::where('course_id', $courseId)
                ->findOrFail($moduleId);
            
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'type' => 'sometimes|in:video,pdf,text,link,quiz,scorm',
                'content' => 'sometimes|string',
                'duration_minutes' => 'nullable|integer|min:1',
                'order' => 'nullable|integer'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $module->update($request->only([
                'title', 'description', 'type', 'content', 'duration_minutes', 'order'
            ]));
            
            return response()->json([
                'success' => true,
                'message' => 'Module updated successfully',
                'data' => $module
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update module: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update module'
            ], 500);
        }
    }
    
    public function deleteModule($courseId, $moduleId): JsonResponse
    {
        try {
            $module = LearningModule::where('course_id', $courseId)
                ->findOrFail($moduleId);
            $module->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Module deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete module: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete module'
            ], 500);
        }
    }
    
    public function uploadModulePdf(Request $request, $courseId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'pdf' => 'required|file|mimes:pdf|max:10240'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $path = $request->file('pdf')->store('learning_modules', 'public');
            
            return response()->json([
                'success' => true,
                'message' => 'PDF uploaded successfully',
                'data' => ['pdf_url' => Storage::url($path)]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to upload PDF: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload PDF'
            ], 500);
        }
    }

    // ── ENROLLMENT ────────────────────────────────────
    
    public function enroll(Request $request, $courseId): JsonResponse
    {
        try {
            $user = Auth::user();
            $employee = Employee::where('user_id', $user->id)->first();
            
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee record not found'
                ], 404);
            }
            
            $course = LearningCourse::findOrFail($courseId);
            
            $existing = LearningEnrollment::where('course_id', $courseId)
                ->where('employee_id', $employee->id)
                ->first();
                
            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Already enrolled in this course'
                ], 400);
            }
            
            if (!$course->allow_self_enroll && $user->role !== 'admin' && $user->role !== 'manager') {
                return response()->json([
                    'success' => false,
                    'message' => 'Self-enrollment is not allowed for this course'
                ], 403);
            }
            
            $enrollment = LearningEnrollment::create([
                'course_id' => $courseId,
                'employee_id' => $employee->id,
                'enrolled_at' => now(),
                'status' => 'enrolled',
                'progress_percent' => 0,
                'enrolled_by' => $user->id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Successfully enrolled in course',
                'data' => $enrollment
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to enroll: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to enroll in course'
            ], 500);
        }
    }
    
    public function bulkEnroll(Request $request, $courseId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_ids' => 'required|array',
                'employee_ids.*' => 'exists:employees,id'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $course = LearningCourse::findOrFail($courseId);
            $enrollments = [];
            
            foreach ($request->employee_ids as $employeeId) {
                $existing = LearningEnrollment::where('course_id', $courseId)
                    ->where('employee_id', $employeeId)
                    ->first();
                    
                if (!$existing) {
                    $enrollments[] = LearningEnrollment::create([
                        'course_id' => $courseId,
                        'employee_id' => $employeeId,
                        'enrolled_at' => now(),
                        'status' => 'enrolled',
                        'progress_percent' => 0,
                        'enrolled_by' => Auth::id()
                    ]);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => count($enrollments) . ' employees enrolled successfully',
                'data' => $enrollments
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to bulk enroll: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk enroll'
            ], 500);
        }
    }
    
    public function unenroll($courseId, $employeeId): JsonResponse
    {
        try {
            $enrollment = LearningEnrollment::where('course_id', $courseId)
                ->where('employee_id', $employeeId)
                ->firstOrFail();
                
            $enrollment->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Employee unenrolled successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to unenroll employee: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to unenroll employee'
            ], 500);
        }
    }

    // ── PROGRESS ─────────────────────────────────────
    
    public function completeModule(Request $request, $courseId, $moduleId): JsonResponse
    {
        try {
            $user = Auth::user();
            $employee = Employee::where('user_id', $user->id)->first();
            
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }
            
            $enrollment = LearningEnrollment::where('course_id', $courseId)
                ->where('employee_id', $employee->id)
                ->firstOrFail();
                
            $progress = LearningModuleProgress::firstOrCreate([
                'enrollment_id' => $enrollment->id,
                'module_id' => $moduleId
            ], [
                'completed' => false,
                'completed_at' => null
            ]);
            
            if (!$progress->completed) {
                $progress->update([
                    'completed' => true,
                    'completed_at' => now()
                ]);
                
                $enrollment->recalculateProgress();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Module marked as complete',
                'data' => ['progress_percent' => $enrollment->fresh()->progress_percent]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to complete module: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete module'
            ], 500);
        }
    }
    
    public function myProgress(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $employee = Employee::where('user_id', $user->id)->first();
            
            if (!$employee) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'stats' => ['enrolled' => 0, 'completed' => 0, 'in_progress' => 0]
                ]);
            }
            
            $enrollments = LearningEnrollment::with(['course', 'moduleProgress.module'])
                ->where('employee_id', $employee->id)
                ->get();
            
            $courses = [];
            $enrolled = 0;
            $completed = 0;
            $inProgress = 0;
            
            foreach ($enrollments as $enrollment) {
                $totalModules = $enrollment->course->modules()->count();
                $completedModules = $enrollment->moduleProgress()->where('completed', true)->count();
                
                $courses[] = [
                    'id' => $enrollment->course->id,
                    'title' => $enrollment->course->title,
                    'category' => $enrollment->course->category,
                    'description' => $enrollment->course->description,
                    'estimated_minutes' => $enrollment->course->estimated_minutes,
                    'enrollment' => $enrollment,
                    'total_modules' => $totalModules,
                    'completed_modules' => $completedModules
                ];
                
                $enrolled++;
                if ($enrollment->status === 'completed') {
                    $completed++;
                } elseif ($enrollment->status === 'in_progress' || $enrollment->progress_percent > 0) {
                    $inProgress++;
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $courses,
                'stats' => [
                    'enrolled' => $enrolled,
                    'completed' => $completed,
                    'in_progress' => $inProgress
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch my progress: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch progress'
            ], 500);
        }
    }

    // ── ASSESSMENT ───────────────────────────────────
    
    public function storeAssessment(Request $request, $courseId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'pass_mark' => 'required|integer|min:0|max:100',
                'max_attempts' => 'nullable|integer|min:1',
                'time_limit_minutes' => 'nullable|integer|min:1'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $assessment = LearningAssessment::updateOrCreate(
                ['course_id' => $courseId],
                [
                    'title' => $request->title,
                    'pass_mark' => $request->pass_mark,
                    'max_attempts' => $request->max_attempts ?? 3,
                    'time_limit_minutes' => $request->time_limit_minutes
                ]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Assessment saved successfully',
                'data' => $assessment
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save assessment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save assessment'
            ], 500);
        }
    }
    
    public function startAttempt(Request $request, $courseId): JsonResponse
    {
        try {
            $user = Auth::user();
            $employee = Employee::where('user_id', $user->id)->first();
            
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }
            
            $enrollment = LearningEnrollment::where('course_id', $courseId)
                ->where('employee_id', $employee->id)
                ->first();
                
            if (!$enrollment) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must be enrolled in this course to take the assessment'
                ], 403);
            }
                
            $assessment = LearningAssessment::where('course_id', $courseId)->first();
            
            if (!$assessment) {
                return response()->json([
                    'success' => false,
                    'message' => 'No assessment found for this course'
                ], 404);
            }
            
            // Check if already passed
            if ($assessment->hasPassed($enrollment->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assessment already passed'
                ], 400);
            }
            
            // Check attempt limit
            $remainingAttempts = $assessment->getRemainingAttempts($enrollment->id);
            if ($remainingAttempts <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maximum attempts reached'
                ], 400);
            }
            
            $attempt = LearningAttempt::create([
                'enrollment_id' => $enrollment->id,
                'assessment_id' => $assessment->id,
                'started_at' => now(),
                'answers' => [],
                'score' => null,
                'passed' => false
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Assessment attempt started',
                'data' => $attempt
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to start assessment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to start assessment'
            ], 500);
        }
    }
    
    public function submitAttempt(Request $request, $courseId, $attemptId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'answers' => 'required|array'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $attempt = LearningAttempt::with(['enrollment.employee', 'assessment'])
                ->findOrFail($attemptId);
                
            if ($attempt->completed_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'This attempt has already been submitted'
                ], 400);
            }
            
            // Check if time expired
            if ($attempt->isTimeExpired()) {
                $attempt->update(['completed_at' => now()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Time limit exceeded. Attempt automatically submitted.'
                ], 400);
            }
            
            $assessment = $attempt->assessment;
            
            // Get all questions with their correct answers
            $questions = LearningQuestion::with(['options' => function($q) {
                $q->where('is_correct', true);
            }])->where('assessment_id', $assessment->id)->get();
            
            $totalPoints = 0;
            $earnedPoints = 0;
            $detailedAnswers = [];
            
            foreach ($questions as $question) {
                $totalPoints += $question->points;
                $userAnswer = $request->answers[$question->id] ?? null;
                $correctOption = $question->options->first();
                
                $isCorrect = ($correctOption && $userAnswer == $correctOption->id);
                if ($isCorrect) {
                    $earnedPoints += $question->points;
                }
                
                $detailedAnswers[] = [
                    'question_id' => $question->id,
                    'question' => $question->question,
                    'user_answer_id' => $userAnswer,
                    'user_answer_text' => $this->getOptionText($question->id, $userAnswer),
                    'correct_answer_id' => $correctOption?->id,
                    'correct_answer_text' => $correctOption?->option_text,
                    'is_correct' => $isCorrect,
                    'points' => $question->points,
                    'earned_points' => $isCorrect ? $question->points : 0
                ];
            }
            
            $score = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100) : 0;
            $passed = $score >= $assessment->pass_mark;
            
            $attempt->update([
                'answers' => $detailedAnswers,
                'score' => $score,
                'passed' => $passed,
                'completed_at' => now()
            ]);
            
            // Update enrollment status
            if ($passed) {
                $attempt->enrollment->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'progress_percent' => 100
                ]);
            }
            
            $attemptsUsed = LearningAttempt::where('enrollment_id', $attempt->enrollment_id)->count();
            $remainingAttempts = max(0, $assessment->max_attempts - $attemptsUsed);
            
            return response()->json([
                'success' => true,
                'message' => $passed ? 'Assessment passed!' : 'Assessment failed',
                'data' => [
                    'score' => $score,
                    'passed' => $passed,
                    'passing_score' => $assessment->pass_mark,
                    'detailed_answers' => $detailedAnswers,
                    'attempts_used' => $attemptsUsed,
                    'max_attempts' => $assessment->max_attempts,
                    'remaining_attempts' => $remainingAttempts
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to submit assessment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit assessment'
            ], 500);
        }
    }
    
    private function getOptionText($questionId, $optionId)
    {
        if (!$optionId) return null;
        $option = LearningQuestionOption::find($optionId);
        return $option?->option_text;
    }

    // ── CERTIFICATE ───────────────────────────────────
    
    public function getCertificate($courseId): JsonResponse
    {
        try {
            $user = Auth::user();
            $employee = Employee::where('user_id', $user->id)->first();
            
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }
            
            $enrollment = LearningEnrollment::with(['course', 'attempts'])
                ->where('course_id', $courseId)
                ->where('employee_id', $employee->id)
                ->first();
                
            if (!$enrollment || $enrollment->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate not available. You must complete the course first.'
                ], 404);
            }
            
            $certificate = $enrollment->getCertificateData();
            
            if (!$certificate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate not available'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $certificate
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get certificate: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get certificate'
            ], 500);
        }
    }
    
    public function downloadCertificate($courseId)
    {
        // This would generate a PDF certificate
        // For now, redirect to the certificate view
        return redirect("/learning/certificate/{$courseId}");
    }

    // ── REPORTING ─────────────────────────────────────
    
    public function report(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!in_array($user->role, ['admin', 'manager'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view reports'
                ], 403);
            }
            
            $businessId = $user->business_id;
            if (!$businessId) {
                $employee = Employee::where('user_id', $user->id)->first();
                if ($employee) $businessId = $employee->business_id;
            }
            
            $query = LearningEnrollment::with(['course', 'employee.user'])
                ->whereHas('course', function($q) use ($businessId) {
                    $q->where('business_id', $businessId);
                });
                
            if ($request->has('course_id') && $request->course_id) {
                $query->where('course_id', $request->course_id);
            }
            
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }
            
            if ($request->has('date_from')) {
                $query->where('enrolled_at', '>=', $request->date_from);
            }
            
            if ($request->has('date_to')) {
                $query->where('enrolled_at', '<=', $request->date_to);
            }
            
            $enrollments = $query->latest()->paginate($request->get('per_page', 15));
            
            $formattedData = $enrollments->map(function($enrollment) {
                return [
                    'id' => $enrollment->id,
                    'course_id' => $enrollment->course_id,
                    'course_title' => $enrollment->course->title,
                    'employee_id' => $enrollment->employee_id,
                    'employee_name' => $enrollment->employee->user->name ?? 'Unknown',
                    'department' => $enrollment->employee->department_id ?? null,
                    'status' => $enrollment->status,
                    'progress_percent' => $enrollment->progress_percent,
                    'enrolled_at' => $enrollment->enrolled_at,
                    'completed_at' => $enrollment->completed_at
                ];
            });
            
            if ($request->has('export') && $request->export === 'csv') {
                $csvData = $this->generateCsvReport($formattedData);
                return response($csvData)
                    ->withHeaders([
                        'Content-Type' => 'text/csv',
                        'Content-Disposition' => 'attachment; filename="learning_report_' . date('Y-m-d') . '.csv"',
                    ]);
            }
            
            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'current_page' => $enrollments->currentPage(),
                'per_page' => $enrollments->perPage(),
                'total' => $enrollments->total(),
                'last_page' => $enrollments->lastPage()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report'
            ], 500);
        }
    }
    
    private function generateCsvReport($data): string
    {
        $csv = "Employee Name,Department,Course Title,Status,Progress %,Enrolled Date,Completed Date\n";
        
        foreach ($data as $item) {
            $csv .= sprintf(
                '"%s","%s","%s","%s",%d,"%s","%s"' . "\n",
                addslashes($item['employee_name']),
                addslashes($item['department'] ?? ''),
                addslashes($item['course_title']),
                $item['status'],
                $item['progress_percent'],
                $item['enrolled_at'] ?? '',
                $item['completed_at'] ?? ''
            );
        }
        
        return $csv;
    }
    
    public function courseReport($courseId): JsonResponse
    {
        try {
            $course = LearningCourse::with(['enrollments.employee.user', 'enrollments.attempts'])
                ->findOrFail($courseId);
                
            $stats = [
                'total_enrolled' => $course->enrollments->count(),
                'completed' => $course->enrollments->where('status', 'completed')->count(),
                'in_progress' => $course->enrollments->where('status', 'in_progress')->count(),
                'enrolled' => $course->enrollments->where('status', 'enrolled')->count(),
                'average_progress' => round($course->enrollments->avg('progress_percent') ?? 0, 2),
                'pass_rate' => $this->calculatePassRate($course->enrollments)
            ];
            
            return response()->json([
                'success' => true,
                'data' => [
                    'course' => $course,
                    'statistics' => $stats
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate course report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate course report'
            ], 500);
        }
    }
    
    private function calculatePassRate($enrollments): float
    {
        $totalWithAttempts = 0;
        $passed = 0;
        
        foreach ($enrollments as $enrollment) {
            $lastAttempt = $enrollment->attempts->where('completed_at', '!=', null)->last();
            if ($lastAttempt) {
                $totalWithAttempts++;
                if ($lastAttempt->passed) {
                    $passed++;
                }
            }
        }
        
        return $totalWithAttempts > 0 ? round(($passed / $totalWithAttempts) * 100, 2) : 0;
    }
    
    // ── QUESTIONS MANAGEMENT ───────────────────────────────────

    public function getQuestions($assessmentId): JsonResponse
    {
        try {
            $questions = LearningQuestion::with('options')
                ->where('assessment_id', $assessmentId)
                ->orderBy('order')
                ->get();
            
            // Don't reveal correct answers in the response
            $questions->each(function($question) {
                $question->options->each(function($option) {
                    unset($option->is_correct);
                });
            });
            
            return response()->json([
                'success' => true,
                'data' => $questions
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch questions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch questions'
            ], 500);
        }
    }

    public function storeQuestion(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'assessment_id' => 'required|exists:learning_assessments,id',
                'question' => 'required|string',
                'points' => 'integer|min:1',
                'order' => 'integer',
                'options' => 'required|array|min:2',
                'options.*.option_text' => 'required|string',
                'options.*.is_correct' => 'boolean'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            DB::beginTransaction();
            
            $question = LearningQuestion::create([
                'assessment_id' => $request->assessment_id,
                'question' => $request->question,
                'points' => $request->points ?? 1,
                'order' => $request->order ?? 0
            ]);
            
            foreach ($request->options as $index => $optionData) {
                LearningQuestionOption::create([
                    'question_id' => $question->id,
                    'option_text' => $optionData['option_text'],
                    'is_correct' => $optionData['is_correct'] ?? false,
                    'order' => $optionData['order'] ?? $index
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Question created successfully',
                'data' => $question->load('options')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create question: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create question'
            ], 500);
        }
    }

    public function updateQuestion(Request $request, $questionId): JsonResponse
    {
        try {
            $question = LearningQuestion::findOrFail($questionId);
            
            $validator = Validator::make($request->all(), [
                'question' => 'sometimes|string',
                'points' => 'integer|min:1',
                'order' => 'integer',
                'options' => 'sometimes|array|min:2',
                'options.*.option_text' => 'required|string',
                'options.*.is_correct' => 'boolean'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            DB::beginTransaction();
            
            $question->update($request->only(['question', 'points', 'order']));
            
            if ($request->has('options')) {
                LearningQuestionOption::where('question_id', $questionId)->delete();
                
                foreach ($request->options as $index => $optionData) {
                    LearningQuestionOption::create([
                        'question_id' => $question->id,
                        'option_text' => $optionData['option_text'],
                        'is_correct' => $optionData['is_correct'] ?? false,
                        'order' => $optionData['order'] ?? $index
                    ]);
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Question updated successfully',
                'data' => $question->fresh('options')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update question: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update question'
            ], 500);
        }
    }

    public function deleteQuestion($questionId): JsonResponse
    {
        try {
            $question = LearningQuestion::findOrFail($questionId);
            $question->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Question deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete question: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete question'
            ], 500);
        }
    }
    
    // ── EMPLOYEE PROGRESS ───────────────────────────────────
    
    public function getEmployeeProgress($employeeId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if ($user->role !== 'admin' && $user->role !== 'manager') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
            
            $employee = Employee::with('user')->findOrFail($employeeId);
            $enrollments = LearningEnrollment::with(['course', 'moduleProgress.module'])
                ->where('employee_id', $employeeId)
                ->get();
                
            $enrollments = $enrollments->map(function($enrollment) {
                $totalModules = $enrollment->course->modules()->count();
                $completedModules = $enrollment->moduleProgress()->where('completed', true)->count();
                $enrollment->total_modules = $totalModules;
                $enrollment->completed_modules = $completedModules;
                return $enrollment;
            });
            
            return response()->json([
                'success' => true,
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->user->name,
                    'department' => $employee->department_id,
                    'role' => $employee->role
                ],
                'enrollments' => $enrollments
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch employee progress: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch employee progress'
            ], 500);
        }
    }
    
    // ── ASSESSMENT QUESTIONS FOR FRONTEND (without correct answers) ──
    
    public function getAssessmentQuestions($courseId): JsonResponse
    {
        try {
            $assessment = LearningAssessment::where('course_id', $courseId)->first();
            
            if (!$assessment) {
                return response()->json([
                    'success' => false,
                    'message' => 'No assessment found for this course'
                ], 404);
            }
            
            $questions = LearningQuestion::with('options')
                ->where('assessment_id', $assessment->id)
                ->orderBy('order')
                ->get();
            
            $formattedQuestions = $questions->map(function($question) {
                return [
                    'id' => $question->id,
                    'question' => $question->question,
                    'points' => $question->points,
                    'options' => $question->options->map(function($option) {
                        return [
                            'id' => $option->id,
                            'option_text' => $option->option_text
                        ];
                    })
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $formattedQuestions,
                'assessment' => [
                    'id' => $assessment->id,
                    'title' => $assessment->title,
                    'pass_mark' => $assessment->pass_mark,
                    'time_limit_minutes' => $assessment->time_limit_minutes,
                    'max_attempts' => $assessment->max_attempts
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch assessment questions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch questions'
            ], 500);
        }
    }
    
    public function viewAttemptAnswers($attemptId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!in_array($user->role, ['admin', 'manager'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
            
            $attempt = LearningAttempt::with(['enrollment.employee.user', 'enrollment.course'])
                ->findOrFail($attemptId);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'attempt' => $attempt,
                    'employee_name' => $attempt->enrollment->employee->user->name,
                    'course_title' => $attempt->enrollment->course->title,
                    'answers' => $attempt->answers,
                    'score' => $attempt->score,
                    'passed' => $attempt->passed,
                    'completed_at' => $attempt->completed_at
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch attempt answers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch answers'
            ], 500);
        }
    }
}