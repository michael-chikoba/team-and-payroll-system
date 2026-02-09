<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduleReport;
use App\Models\Schedule;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ScheduleReportController extends Controller
{
    /**
     * Submit a report for a schedule (Employee)
     * Supports both text and file uploads
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'schedule_id' => 'required|exists:schedules,id',
                'report_type' => 'required|in:text,file,both',
                'report_content' => 'required_if:report_type,text,both|string',
                'report_file' => 'required_if:report_type,file,both|file|mimes:pdf,doc,docx,txt|max:10240', // Max 10MB
                'metadata' => 'nullable|array',
                'metadata.hours_worked' => 'nullable|numeric',
                'metadata.tasks_completed' => 'nullable|integer',
                'metadata.challenges' => 'nullable|string',
                'metadata.achievements' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = Auth::id();
            $employee = Employee::where('user_id', $userId)->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee record not found'
                ], 404);
            }

            // Verify the schedule is assigned to this employee
            $schedule = Schedule::findOrFail($request->schedule_id);
            
            if ($schedule->assigned_to != $employee->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to submit a report for this schedule'
                ], 403);
            }

            // Check if report already exists
            $existingReport = ScheduleReport::where('schedule_id', $request->schedule_id)
                ->where('employee_id', $employee->id)
                ->first();

            if ($existingReport) {
                return response()->json([
                    'success' => false,
                    'message' => 'A report has already been submitted for this schedule'
                ], 409);
            }

            // Prepare data
            $reportData = [
                'schedule_id' => $request->schedule_id,
                'employee_id' => $employee->id,
                'submitted_by' => $userId,
                'report_type' => $request->report_type,
                'report_content' => $request->report_content,
                'metadata' => $request->metadata ?? [],
                'status' => 'submitted'
            ];

            // Handle file upload
            if ($request->hasFile('report_file')) {
                $file = $request->file('report_file');
                
                // Generate unique filename
                $fileName = time() . '_' . $employee->id . '_' . $file->getClientOriginalName();
                
                // Store file in storage/app/public/schedule-reports
                $filePath = $file->storeAs('schedule-reports', $fileName, 'public');
                
                $reportData['file_path'] = $filePath;
                $reportData['file_name'] = $file->getClientOriginalName();
                $reportData['file_type'] = $file->getMimeType();
                $reportData['file_size'] = $file->getSize();
            }

            $report = ScheduleReport::create($reportData);

            Log::info('Schedule report submitted', [
                'report_id' => $report->id,
                'schedule_id' => $request->schedule_id,
                'employee_id' => $employee->id,
                'report_type' => $request->report_type,
                'has_file' => $request->hasFile('report_file')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Report submitted successfully',
                'report' => $report->load(['schedule', 'employee'])
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to submit schedule report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download report file
     * RENAMED FROM downloadFile() to download() to match the route
     */
    public function download($id)
    {
        try {
            $report = ScheduleReport::findOrFail($id);

            Log::info('Download request for report', [
                'report_id' => $id,
                'file_path' => $report->file_path,
                'file_name' => $report->file_name,
                'user_id' => Auth::id()
            ]);

            // Check authorization
            $userId = Auth::id();
            $employee = Employee::where('user_id', $userId)->first();
            $managedEmployeeIds = Employee::where('manager_id', $userId)->pluck('id');

            $isOwner = $employee && $report->employee_id == $employee->id;
            $isManager = $managedEmployeeIds->contains($report->employee_id);

            if (!$isOwner && !$isManager) {
                Log::warning('Unauthorized download attempt', [
                    'report_id' => $id,
                    'user_id' => $userId,
                    'employee_id' => $employee ? $employee->id : null
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to download this file'
                ], 403);
            }

            if (!$report->file_path) {
                Log::error('No file path in report', ['report_id' => $id]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'No file attached to this report'
                ], 404);
            }

            // Check if file exists in public disk
            if (!Storage::disk('public')->exists($report->file_path)) {
                Log::error('File not found in storage', [
                    'report_id' => $id,
                    'file_path' => $report->file_path,
                    'full_path' => Storage::disk('public')->path($report->file_path)
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'File not found in storage'
                ], 404);
            }

            Log::info('File download successful', [
                'report_id' => $id,
                'file_name' => $report->file_name
            ]);

            return Storage::disk('public')->download($report->file_path, $report->file_name);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Report not found', ['report_id' => $id]);
            
            return response()->json([
                'success' => false,
                'message' => 'Report not found'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Failed to download report file', [
                'error' => $e->getMessage(),
                'report_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to download file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get reports (filtered by employee, date, status) - Manager
     */
    public function index(Request $request)
    {
        try {
            $userId = Auth::id();
            
            // Check if user is a manager by checking if they have any managed employees
            $managedEmployeeIds = Employee::where('manager_id', $userId)->pluck('id');
            
            if ($managedEmployeeIds->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view schedule reports. Only managers can access this resource.'
                ], 403);
            }

            $query = ScheduleReport::with([
                'schedule',
                'employee.user',
                'submitter',
                'reviewer'
            ]);

            // Apply filters
            $query->byEmployee($request->employee_id)
                  ->byDateRange($request->start_date, $request->end_date)
                  ->byStatus($request->status);

            // Manager filter - only show reports from managed employees
            $query->whereIn('employee_id', $managedEmployeeIds);

            // Sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->input('per_page', 15);
            $reports = $query->paginate($perPage);

            // Transform reports to ensure employee names are available
            $reports->getCollection()->transform(function ($report) {
                if ($report->employee) {
                    // Build employee name from user relationship if not already set
                    if (!isset($report->employee->name) && $report->employee->user) {
                        $report->employee->name = trim(
                            ($report->employee->user->first_name ?? '') . ' ' . 
                            ($report->employee->user->last_name ?? '')
                        );
                    }
                }
                return $report;
            });

            // Statistics
            $stats = [
                'total' => ScheduleReport::whereIn('employee_id', $managedEmployeeIds)->count(),
                'submitted' => ScheduleReport::whereIn('employee_id', $managedEmployeeIds)->where('status', 'submitted')->count(),
                'reviewed' => ScheduleReport::whereIn('employee_id', $managedEmployeeIds)->where('status', 'reviewed')->count(),
                'approved' => ScheduleReport::whereIn('employee_id', $managedEmployeeIds)->where('status', 'approved')->count(),
                'rejected' => ScheduleReport::whereIn('employee_id', $managedEmployeeIds)->where('status', 'rejected')->count()
            ];

            return response()->json([
                'success' => true,
                'reports' => $reports,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch schedule reports', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee's own reports
     */
    public function myReports(Request $request)
    {
        try {
            $userId = Auth::id();
            $employee = Employee::where('user_id', $userId)->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee record not found'
                ], 404);
            }

            $query = ScheduleReport::with(['schedule', 'reviewer'])
                ->where('employee_id', $employee->id);

            // Apply filters
            $query->byDateRange($request->start_date, $request->end_date)
                  ->byStatus($request->status);

            $reports = $query->orderBy('created_at', 'desc')->paginate(10);

            return response()->json([
                'success' => true,
                'reports' => $reports
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch employee reports', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reports'
            ], 500);
        }
    }

    /**
     * Review a report (Manager)
     */
    public function review(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:reviewed,approved,rejected',
                'review_comments' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = Auth::id();
            
            // Verify manager has access to this report
            $managedEmployeeIds = Employee::where('manager_id', $userId)->pluck('id');
            
            if ($managedEmployeeIds->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to review reports. Only managers can access this resource.'
                ], 403);
            }

            $report = ScheduleReport::findOrFail($id);
            
            if (!$managedEmployeeIds->contains($report->employee_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to review this report'
                ], 403);
            }

            $report->update([
                'status' => $request->status,
                'reviewed_by' => $userId,
                'reviewed_at' => now(),
                'review_comments' => $request->review_comments
            ]);

            Log::info('Report reviewed', [
                'report_id' => $id,
                'status' => $request->status,
                'reviewed_by' => $userId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Report reviewed successfully',
                'report' => $report->load(['schedule', 'employee', 'reviewer'])
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to review report', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to review report'
            ], 500);
        }
    }

    /**
     * Get single report details
     */
    public function show($id)
    {
        try {
            $report = ScheduleReport::with([
                'schedule',
                'employee',
                'submitter',
                'reviewer'
            ])->findOrFail($id);

            // Check authorization
            $userId = Auth::id();
            $employee = Employee::where('user_id', $userId)->first();
            $managedEmployeeIds = Employee::where('manager_id', $userId)->pluck('id');

            $isOwner = $employee && $report->employee_id == $employee->id;
            $isManager = $managedEmployeeIds->contains($report->employee_id);

            if (!$isOwner && !$isManager) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view this report'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'report' => $report
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found'
            ], 404);
        }
    }
}