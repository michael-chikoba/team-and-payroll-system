<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class TaskReportController extends Controller
{
    /**
     * Generate task report
     */
    public function generateReport(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Check if user exists and has employee relationship
            if (!$user) {
                Log::error('User not authenticated');
                return response()->json([
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $userEmployee = $user->employee;
            
            // Check if employee exists
            if (!$userEmployee) {
                Log::error('No employee record found for user', ['user_id' => $user->id]);
                return response()->json([
                    'message' => 'You must have an employee profile to generate reports'
                ], 400);
            }
            
            Log::info('Generating task report', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'employee_id' => $userEmployee->id,
                'request_data' => $request->all()
            ]);

            // Validate request
            $validated = $request->validate([
                'task_ids' => 'required|array',
                'task_ids.*' => 'exists:tasks,id',
                'report_type' => 'required|in:weekly,custom,all',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'include_details' => 'boolean',
                'include_comments' => 'boolean',
                'include_subtasks' => 'boolean',
                'include_descriptions' => 'boolean',
                'format' => 'required|in:pdf,excel'
            ]);

            // Ensure descriptions are included by default if not specified
            if (!isset($validated['include_descriptions'])) {
                $validated['include_descriptions'] = true; // Always include descriptions
            }

            // Get user's business
            if (!$userEmployee->business_id) {
                return response()->json([
                    'message' => 'You must be associated with a business to generate reports'
                ], 400);
            }

            // Get tasks with authorization check
            $tasks = Task::with([
                'assignedTo.employee',
                'createdBy.employee',
                'comments.user',
                'subtasks.assignee',
                'history.user',
                'workLogs'
            ])
            ->whereIn('id', $validated['task_ids'])
            ->get()
            ->filter(function ($task) use ($user, $userEmployee) {
                // Authorization: user can only access tasks in their business
                $taskAssignedEmployee = $task->assignedTo->employee ?? null;
                $taskCreatorEmployee = $task->createdBy->employee ?? null;
                
                return ($taskAssignedEmployee && $taskAssignedEmployee->business_id === $userEmployee->business_id) ||
                       ($taskCreatorEmployee && $taskCreatorEmployee->business_id === $userEmployee->business_id);
            });

            if ($tasks->isEmpty()) {
                return response()->json([
                    'message' => 'No tasks found or unauthorized access'
                ], 404);
            }

            // Prepare report data
            $reportData = $this->prepareReportData($tasks, $validated, $user);
            
            // Generate report based on format
            if ($validated['format'] === 'pdf') {
                return $this->generatePdfReport($reportData, $validated);
            } else {
                return $this->generateExcelReport($reportData, $validated);
            }

        } catch (\Exception $e) {
            Log::error('Failed to generate task report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Failed to generate report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prepare report data
     */
    private function prepareReportData($tasks, $options, $user)
    {
        $business = $user->employee->business ?? null;
        
        $reportData = [
            'title' => $this->getReportTitle($options),
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'generated_by' => $user->first_name . ' ' . $user->last_name,
            'business_name' => $business->name ?? 'N/A',
            'business_details' => $business ? [
                'address' => $business->address,
                'phone' => $business->phone,
                'email' => $business->email,
                'website' => $business->website,
            ] : null,
            'total_tasks' => $tasks->count(),
            'summary' => $this->generateSummary($tasks),
            'detailed_summary' => $this->generateDetailedSummary($tasks),
            'tasks' => $tasks->map(function ($task) use ($options) {
                return $this->formatTaskData($task, $options);
            })->toArray(),
        ];

        return $reportData;
    }

    /**
     * Get report title based on type
     */
    private function getReportTitle($options)
    {
        switch ($options['report_type']) {
            case 'weekly':
                $weekStart = now()->startOfWeek()->format('M d, Y');
                $weekEnd = now()->endOfWeek()->format('M d, Y');
                return "Weekly Task Report ({$weekStart} - {$weekEnd})";
            case 'custom':
                $start = $options['start_date'] ? date('M d, Y', strtotime($options['start_date'])) : 'N/A';
                $end = $options['end_date'] ? date('M d, Y', strtotime($options['end_date'])) : 'N/A';
                return "Custom Task Report ({$start} - {$end})";
            default:
                return "Complete Task Report";
        }
    }

    /**
     * Generate summary statistics
     */
    private function generateSummary($tasks)
    {
        $statusCounts = [
            'todo' => 0,
            'in_progress' => 0,
            'under_review' => 0,
            'completed' => 0
        ];

        $priorityCounts = [
            'low' => 0,
            'moderate' => 0,
            'high' => 0,
            'critical' => 0
        ];

        $overdueCount = 0;
        $totalSubtasks = 0;
        $totalComments = 0;
        $totalWorkHours = 0;

        foreach ($tasks as $task) {
            $statusCounts[$task->status]++;
            $priorityCounts[$task->priority]++;
            
            if ($task->deadline && $task->status !== 'completed') {
                if (new \DateTime($task->deadline) < new \DateTime()) {
                    $overdueCount++;
                }
            }
            
            $totalSubtasks += $task->subtasks->count();
            $totalComments += $task->comments->count();
            $totalWorkHours += $task->workLogs->sum('hours');
        }

        return [
            'status_counts' => $statusCounts,
            'priority_counts' => $priorityCounts,
            'overdue_count' => $overdueCount,
            'total_subtasks' => $totalSubtasks,
            'total_comments' => $totalComments,
            'total_work_hours' => $totalWorkHours,
            'completion_rate' => $tasks->count() > 0 
                ? round(($statusCounts['completed'] / $tasks->count()) * 100, 2)
                : 0,
            'average_priority' => $this->calculateAveragePriority($priorityCounts, $tasks->count()),
        ];
    }

    /**
     * Generate detailed summary
     */
    private function generateDetailedSummary($tasks)
    {
        $tasksByAssignee = [];
        $tasksByPriority = [];
        $tasksByStatus = [];
        $recentlyCompleted = [];
        $upcomingDeadlines = [];
        $averageCompletionTime = 0;
        
        $now = new \DateTime();
        $thirtyDaysAgo = (clone $now)->modify('-30 days');
        $sevenDaysFromNow = (clone $now)->modify('+7 days');
        
        $completedTasks = $tasks->where('status', 'completed');
        $completionTimes = [];
        
        foreach ($tasks as $task) {
            // Group by assignee
            $assigneeName = $task->assignedTo ? 
                $task->assignedTo->first_name . ' ' . $task->assignedTo->last_name : 
                'Unassigned';
            
            if (!isset($tasksByAssignee[$assigneeName])) {
                $tasksByAssignee[$assigneeName] = 0;
            }
            $tasksByAssignee[$assigneeName]++;
            
            // Group by priority
            $priority = ucfirst($task->priority);
            if (!isset($tasksByPriority[$priority])) {
                $tasksByPriority[$priority] = 0;
            }
            $tasksByPriority[$priority]++;
            
            // Group by status
            $status = ucfirst(str_replace('_', ' ', $task->status));
            if (!isset($tasksByStatus[$status])) {
                $tasksByStatus[$status] = 0;
            }
            $tasksByStatus[$status]++;
            
            // Recently completed tasks (last 30 days)
            if ($task->status === 'completed' && $task->completed_at) {
                $completedAt = new \DateTime($task->completed_at);
                if ($completedAt > $thirtyDaysAgo) {
                    $recentlyCompleted[] = [
                        'id' => $task->id,
                        'title' => $task->title,
                        'completed_at' => $completedAt->format('Y-m-d'),
                        'completed_by' => $task->assignedTo ? 
                            $task->assignedTo->first_name . ' ' . $task->assignedTo->last_name : 
                            'Unknown',
                    ];
                }
                
                // Calculate completion time for completed tasks
                if ($task->created_at && $task->completed_at) {
                    $createdAt = new \DateTime($task->created_at);
                    $completedAt = new \DateTime($task->completed_at);
                    $diff = $createdAt->diff($completedAt);
                    $completionTimes[] = $diff->days * 24 + $diff->h + ($diff->i / 60); // Hours
                }
            }
            
            // Upcoming deadlines (next 7 days)
            if ($task->deadline && $task->status !== 'completed') {
                $deadline = new \DateTime($task->deadline);
                if ($deadline > $now && $deadline <= $sevenDaysFromNow) {
                    $upcomingDeadlines[] = [
                        'id' => $task->id,
                        'title' => $task->title,
                        'deadline' => $deadline->format('Y-m-d'),
                        'priority' => ucfirst($task->priority),
                        'assignee' => $task->assignedTo ? 
                            $task->assignedTo->first_name . ' ' . $task->assignedTo->last_name : 
                            'Unassigned',
                    ];
                }
            }
        }
        
        // Calculate average completion time
        if (count($completionTimes) > 0) {
            $averageCompletionTime = round(array_sum($completionTimes) / count($completionTimes), 1);
        }
        
        // Sort arrays
        arsort($tasksByAssignee);
        arsort($tasksByPriority);
        arsort($tasksByStatus);
        
        // Sort recently completed by date (newest first)
        usort($recentlyCompleted, function($a, $b) {
            return strtotime($b['completed_at']) - strtotime($a['completed_at']);
        });
        
        // Sort upcoming deadlines by date (closest first)
        usort($upcomingDeadlines, function($a, $b) {
            return strtotime($a['deadline']) - strtotime($b['deadline']);
        });
        
        return [
            'tasks_by_assignee' => $tasksByAssignee,
            'tasks_by_priority' => $tasksByPriority,
            'tasks_by_status' => $tasksByStatus,
            'recently_completed' => array_slice($recentlyCompleted, 0, 10), // Top 10
            'upcoming_deadlines' => array_slice($upcomingDeadlines, 0, 10), // Top 10
            'average_completion_time_hours' => $averageCompletionTime,
            'most_active_assignee' => count($tasksByAssignee) > 0 ? 
                array_key_first($tasksByAssignee) : 'None',
            'most_common_priority' => count($tasksByPriority) > 0 ? 
                array_key_first($tasksByPriority) : 'None',
        ];
    }

    /**
     * Calculate average priority score
     */
    private function calculateAveragePriority($priorityCounts, $totalTasks)
    {
        if ($totalTasks === 0) return 'N/A';
        
        $priorityScores = [
            'low' => 1,
            'moderate' => 2,
            'high' => 3,
            'critical' => 4
        ];
        
        $totalScore = 0;
        foreach ($priorityCounts as $priority => $count) {
            if (isset($priorityScores[$priority])) {
                $totalScore += $priorityScores[$priority] * $count;
            }
        }
        
        $averageScore = $totalScore / $totalTasks;
        
        if ($averageScore >= 3.5) return 'Critical';
        if ($averageScore >= 2.5) return 'High';
        if ($averageScore >= 1.5) return 'Moderate';
        return 'Low';
    }

    /**
     * Format task data for report
     */
    private function formatTaskData($task, $options)
    {
        $formattedTask = [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description ?? '',
            'priority' => ucfirst($task->priority),
            'status' => ucfirst(str_replace('_', ' ', $task->status)),
            'created_at' => $task->created_at->format('Y-m-d H:i'),
            'updated_at' => $task->updated_at->format('Y-m-d H:i'),
            'completed_at' => $task->completed_at ? $task->completed_at->format('Y-m-d H:i') : null,
            'deadline' => $task->deadline ? $task->deadline->format('Y-m-d') : 'Not set',
            'deadline_full' => $task->deadline ? $task->deadline->format('F j, Y \a\t h:i A') : 'Not set',
            'assigned_to' => $task->assignedTo ? 
                $task->assignedTo->first_name . ' ' . $task->assignedTo->last_name : 
                'Unassigned',
            'created_by' => $task->createdBy ? 
                $task->createdBy->first_name . ' ' . $task->createdBy->last_name : 
                'Unknown',
            'is_overdue' => $task->deadline && $task->status !== 'completed' && 
                          new \DateTime($task->deadline) < new \DateTime(),
            'total_hours_logged' => $task->workLogs->sum('hours'),
            'total_comments' => $task->comments->count(),
            'total_subtasks' => $task->subtasks->count(),
            'has_description' => !empty($task->description),
        ];

        // Always include description in formatted data
        $formattedTask['full_description'] = $task->description ?? 'No description provided';
        
        // Format description for display (remove HTML tags, limit length if needed)
        $formattedTask['formatted_description'] = $this->formatDescriptionForDisplay($task->description);
        
        // Include HTML-safe description for PDF rendering
        $formattedTask['html_description'] = nl2br(e($task->description ?? 'No description provided'));

        if ($options['include_details']) {
            $formattedTask['details'] = [
                'total_hours_logged' => $task->workLogs->sum('hours'),
                'last_updated' => $task->updated_at->format('Y-m-d H:i'),
                'time_estimate' => $task->time_estimate ?? 'Not set',
                'actual_time_spent' => $task->actual_time_spent ?? 'Not tracked',
                'progress_percentage' => $task->progress_percentage ?? 0,
            ];
        }

        if ($options['include_comments'] && $task->comments->isNotEmpty()) {
            $formattedTask['comments'] = $task->comments->map(function ($comment) {
                return [
                    'user' => $comment->user ? 
                        $comment->user->first_name . ' ' . $comment->user->last_name : 
                        'Unknown User',
                    'comment' => $comment->comment,
                    'date' => $comment->created_at->format('Y-m-d H:i'),
                    'type' => $comment->type ?? 'comment',
                ];
            })->toArray();
        }

        if ($options['include_subtasks'] && $task->subtasks->isNotEmpty()) {
            $formattedTask['subtasks'] = $task->subtasks->map(function ($subtask) {
                return [
                    'id' => $subtask->id,
                    'title' => $subtask->title,
                    'description' => $subtask->description ?? '',
                    'formatted_description' => $this->formatDescriptionForDisplay($subtask->description),
                    'status' => ucfirst($subtask->status),
                    'priority' => ucfirst($subtask->priority),
                    'assignee' => $subtask->assignee ? 
                        $subtask->assignee->first_name . ' ' . $subtask->assignee->last_name : 
                        'Not assigned',
                    'created_by' => $subtask->creator ? 
                        $subtask->creator->first_name . ' ' . $subtask->creator->last_name : 
                        'Unknown',
                    'created_at' => $subtask->created_at->format('Y-m-d H:i'),
                    'order' => $subtask->order,
                ];
            })->toArray();
        }

        return $formattedTask;
    }

    /**
     * Format description for display
     */
    private function formatDescriptionForDisplay($description)
    {
        if (empty($description)) {
            return 'No description provided';
        }
        
        // Remove HTML tags
        $description = strip_tags($description);
        
        // Convert newlines to spaces
        $description = str_replace(["\r\n", "\r", "\n"], ' ', $description);
        
        // Trim and limit length for display
        $description = trim($description);
        
        return $description;
    }

    /**
     * Generate PDF report
     */
    private function generatePdfReport($reportData, $options)
    {
        // Clear any cached PDF views
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        // Clear Laravel view cache
        $viewsPath = storage_path('framework/views');
        if (is_dir($viewsPath)) {
            array_map('unlink', glob("$viewsPath/*"));
        }

        // Force PDF to use our view
        $pdf = Pdf::loadView('reports.task-report', [
            'reportData' => $reportData,
            'options' => $options
        ]);

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'dpi' => 150,
            'defaultFont' => 'dejavu sans',
        ]);
        
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="task-report-' . date('Y-m-d-H-i-s') . '.pdf"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Generate Excel report
     */
    private function generateExcelReport($reportData, $options)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set report title
        $sheet->setCellValue('A1', $reportData['title']);
        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Report metadata
        $sheet->setCellValue('A3', 'Generated By:');
        $sheet->setCellValue('B3', $reportData['generated_by']);
        $sheet->setCellValue('A4', 'Business:');
        $sheet->setCellValue('B4', $reportData['business_name']);
        $sheet->setCellValue('A5', 'Generated At:');
        $sheet->setCellValue('B5', $reportData['generated_at']);
        $sheet->setCellValue('A6', 'Total Tasks:');
        $sheet->setCellValue('B6', $reportData['total_tasks']);

        // Summary section
        $summaryRow = 8;
        $sheet->setCellValue("A{$summaryRow}", 'Summary Statistics');
        $sheet->mergeCells("A{$summaryRow}:K{$summaryRow}");
        $sheet->getStyle("A{$summaryRow}")->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle("A{$summaryRow}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2E8F0');

        $summaryRow++;
        $sheet->setCellValue("A{$summaryRow}", 'Status');
        $sheet->setCellValue("B{$summaryRow}", 'Count');
        $sheet->setCellValue("C{$summaryRow}", 'Priority');
        $sheet->setCellValue("D{$summaryRow}", 'Count');
        
        $summaryRow++;
        foreach ($reportData['summary']['status_counts'] as $status => $count) {
            $sheet->setCellValue("A{$summaryRow}", ucfirst(str_replace('_', ' ', $status)));
            $sheet->setCellValue("B{$summaryRow}", $count);
            $summaryRow++;
        }

        $summaryRow = 10;
        foreach ($reportData['summary']['priority_counts'] as $priority => $count) {
            $sheet->setCellValue("C{$summaryRow}", ucfirst($priority));
            $sheet->setCellValue("D{$summaryRow}", $count);
            $summaryRow++;
        }

        // Additional summary stats
        $summaryRow = max(array_keys($reportData['summary']['status_counts'])) + 12;
        $sheet->setCellValue("A{$summaryRow}", 'Overdue Tasks:');
        $sheet->setCellValue("B{$summaryRow}", $reportData['summary']['overdue_count']);
        $sheet->setCellValue("A" . ($summaryRow + 1), 'Total Subtasks:');
        $sheet->setCellValue("B" . ($summaryRow + 1), $reportData['summary']['total_subtasks']);
        $sheet->setCellValue("A" . ($summaryRow + 2), 'Total Comments:');
        $sheet->setCellValue("B" . ($summaryRow + 2), $reportData['summary']['total_comments']);
        $sheet->setCellValue("A" . ($summaryRow + 3), 'Total Work Hours:');
        $sheet->setCellValue("B" . ($summaryRow + 3), $reportData['summary']['total_work_hours']);
        $sheet->setCellValue("A" . ($summaryRow + 4), 'Completion Rate:');
        $sheet->setCellValue("B" . ($summaryRow + 4), $reportData['summary']['completion_rate'] . '%');
        $sheet->setCellValue("A" . ($summaryRow + 5), 'Average Priority:');
        $sheet->setCellValue("B" . ($summaryRow + 5), $reportData['summary']['average_priority']);

        // Detailed summary section
        $detailedRow = $summaryRow + 8;
        $sheet->setCellValue("A{$detailedRow}", 'Detailed Analysis');
        $sheet->mergeCells("A{$detailedRow}:K{$detailedRow}");
        $sheet->getStyle("A{$detailedRow}")->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle("A{$detailedRow}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2E8F0');

        // Tasks by Assignee
        $detailedRow++;
        $sheet->setCellValue("A{$detailedRow}", 'Tasks by Assignee');
        $sheet->getStyle("A{$detailedRow}")->getFont()->setBold(true);
        $detailedRow++;
        
        $sheet->setCellValue("A{$detailedRow}", 'Assignee');
        $sheet->setCellValue("B{$detailedRow}", 'Task Count');
        $sheet->getStyle("A{$detailedRow}:B{$detailedRow}")->getFont()->setBold(true);
        $detailedRow++;
        
        foreach ($reportData['detailed_summary']['tasks_by_assignee'] as $assignee => $count) {
            $sheet->setCellValue("A{$detailedRow}", $assignee);
            $sheet->setCellValue("B{$detailedRow}", $count);
            $detailedRow++;
        }

        // Tasks by Priority
        $detailedRow++;
        $sheet->setCellValue("A{$detailedRow}", 'Tasks by Priority');
        $sheet->getStyle("A{$detailedRow}")->getFont()->setBold(true);
        $detailedRow++;
        
        $sheet->setCellValue("A{$detailedRow}", 'Priority');
        $sheet->setCellValue("B{$detailedRow}", 'Task Count');
        $sheet->getStyle("A{$detailedRow}:B{$detailedRow}")->getFont()->setBold(true);
        $detailedRow++;
        
        foreach ($reportData['detailed_summary']['tasks_by_priority'] as $priority => $count) {
            $sheet->setCellValue("A{$detailedRow}", $priority);
            $sheet->setCellValue("B{$detailedRow}", $count);
            $detailedRow++;
        }

        // Tasks header
        $headerRow = $detailedRow + 3;
        $headers = ['ID', 'Title', 'Description', 'Priority', 'Status', 'Assigned To', 'Created By', 'Deadline', 'Created At', 'Completed At', 'Hours Logged'];
        
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue("{$col}{$headerRow}", $header);
            $sheet->getStyle("{$col}{$headerRow}")->getFont()->setBold(true);
            $sheet->getStyle("{$col}{$headerRow}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF4299E1');
            $sheet->getStyle("{$col}{$headerRow}")->getFont()->getColor()->setARGB('FFFFFFFF');
            $col++;
        }

        // Tasks data
        $dataRow = $headerRow + 1;
        foreach ($reportData['tasks'] as $task) {
            $sheet->setCellValue("A{$dataRow}", $task['id']);
            $sheet->setCellValue("B{$dataRow}", $task['title']);
            
            // Set description with proper data type
            $description = $task['formatted_description'] ?? $task['description'] ?? '';
            $sheet->setCellValueExplicit("C{$dataRow}", $description, DataType::TYPE_STRING);
            
            $sheet->setCellValue("D{$dataRow}", $task['priority']);
            $sheet->setCellValue("E{$dataRow}", $task['status']);
            $sheet->setCellValue("F{$dataRow}", $task['assigned_to']);
            $sheet->setCellValue("G{$dataRow}", $task['created_by']);
            $sheet->setCellValue("H{$dataRow}", $task['deadline_full']);
            $sheet->setCellValue("I{$dataRow}", $task['created_at']);
            $sheet->setCellValue("J{$dataRow}", $task['completed_at'] ?? 'N/A');
            $sheet->setCellValue("K{$dataRow}", $task['total_hours_logged']);

            // Style overdue tasks
            if ($task['is_overdue']) {
                $sheet->getStyle("A{$dataRow}:K{$dataRow}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFED7D7');
            }

            // Style priorities
            $priorityColor = $this->getPriorityColor($task['priority']);
            $sheet->getStyle("D{$dataRow}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($priorityColor);

            // Set row height for description cells
            $sheet->getRowDimension($dataRow)->setRowHeight(40);

            $dataRow++;
        }

        // Auto-size columns
        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Set specific column widths
        $sheet->getColumnDimension('C')->setWidth(60); // Description column wider
        $sheet->getColumnDimension('B')->setWidth(40); // Title column
        $sheet->getColumnDimension('H')->setWidth(25); // Deadline column
        $sheet->getColumnDimension('F')->setWidth(25); // Assigned To column
        $sheet->getColumnDimension('G')->setWidth(25); // Created By column
        
        // Wrap text for description column
        $sheet->getStyle('C' . ($headerRow + 1) . ':C' . ($dataRow - 1))
            ->getAlignment()->setWrapText(true);

        // Add borders
        $lastRow = $dataRow - 1;
        $styleArray = [
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
                'inside' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];
        $sheet->getStyle("A{$headerRow}:K{$lastRow}")->applyFromArray($styleArray);

        // Add alternate row coloring
        $alternateRowStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF8F9FA'],
            ],
        ];
        
        for ($row = $headerRow + 2; $row <= $lastRow; $row += 2) {
            $sheet->getStyle("A{$row}:K{$row}")->applyFromArray($alternateRowStyle);
        }

        // Save to temporary file
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'task_report_') . '.xlsx';
        $writer->save($tempFile);

        return response()->download($tempFile, 'task-report-' . date('Y-m-d') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Get color for priority
     */
    private function getPriorityColor($priority)
    {
        $colors = [
            'Critical' => 'FFE53E3E',
            'High' => 'FFED8936',
            'Moderate' => 'FFECC94B',
            'Low' => 'FF48BB78'
        ];
        
        return $colors[$priority] ?? 'FFFFFFFF';
    }

    /**
     * Get available report filters
     */
    public function getFilters()
    {
        try {
            $user = Auth::user();
            $userEmployee = $user->employee;
            
            if (!$userEmployee || !$userEmployee->business_id) {
                return response()->json([
                    'filters' => [
                        'priorities' => ['low', 'moderate', 'high', 'critical'],
                        'statuses' => ['todo', 'in_progress', 'under_review', 'completed'],
                        'assignees' => []
                    ]
                ]);
            }

            // Get employees in the same business for assignee filter
            $assignees = Employee::with('user')
                ->where('business_id', $userEmployee->business_id)
                ->get()
                ->map(function ($employee) {
                    return [
                        'id' => $employee->user_id,
                        'name' => $employee->full_name,
                        'position' => $employee->position
                    ];
                });

            return response()->json([
                'filters' => [
                    'priorities' => ['low', 'moderate', 'high', 'critical'],
                    'statuses' => ['todo', 'in_progress', 'under_review', 'completed'],
                    'assignees' => $assignees,
                    'include_options' => [
                        'details' => 'Include Task Details',
                        'comments' => 'Include Comments',
                        'subtasks' => 'Include Subtasks',
                        'descriptions' => 'Include Descriptions'
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get report filters', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to get filters'], 500);
        }
    }
}