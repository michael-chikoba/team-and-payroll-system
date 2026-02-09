<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Task;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductivityController extends Controller
{
    /**
     * Get productivity data with proper SLA tracking
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Check if user is admin or manager
            if (!$user->hasRole('admin') && !$user->hasRole('manager')) {
                return response()->json([
                    'message' => 'Unauthorized access. You need admin or manager role.',
                    'user_roles' => $user->roles->pluck('name')
                ], 403);
            }

            // Get date range from request
            $dateRange = $this->getDateRange($request);
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];

            Log::info('Fetching productivity data with SLA tracking', [
                'user_id' => $user->id,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString()
            ]);

            // Get employee data
            $employees = $this->getEmployeeData($user, $startDate, $endDate);
            
            // Calculate overview metrics
            $overview = $this->calculateOverview($employees, $startDate, $endDate);
            
            // Calculate SLA details
            $slaDetails = $this->calculateSlaDetails($user, $startDate, $endDate);
            
            // Calculate task analysis
            $taskAnalysis = $this->calculateTaskAnalysis($user, $startDate, $endDate);
            
            // Generate chart data
            $charts = $this->generateChartData($user, $startDate, $endDate);

            return response()->json([
                'employees' => $employees,
                'overview' => $overview,
                'sla_details' => $slaDetails,
                'task_analysis' => $taskAnalysis,
                'charts' => $charts,
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString()
                ],
                'user_info' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch productivity data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'message' => 'Failed to fetch productivity data: ' . $e->getMessage(),
                'employees' => [],
                'overview' => $this->getDefaultOverview(),
                'sla_details' => $this->getDefaultSlaDetails(),
                'task_analysis' => $this->getDefaultTaskAnalysis(),
                'charts' => $this->getDefaultChartData()
            ], 500);
        }
    }

    /**
     * Get date range based on request parameters
     */
    private function getDateRange(Request $request)
    {
        $period = $request->input('period', 'last_30_days');
        
        switch ($period) {
            case 'last_7_days':
                return [
                    'start' => Carbon::now()->subDays(7)->startOfDay(),
                    'end' => Carbon::now()->endOfDay()
                ];
            
            case 'last_30_days':
                return [
                    'start' => Carbon::now()->subDays(30)->startOfDay(),
                    'end' => Carbon::now()->endOfDay()
                ];
            
            case 'this_month':
                return [
                    'start' => Carbon::now()->startOfMonth(),
                    'end' => Carbon::now()->endOfDay()
                ];
            
            case 'last_month':
                return [
                    'start' => Carbon::now()->subMonth()->startOfMonth(),
                    'end' => Carbon::now()->subMonth()->endOfMonth()
                ];
            
            case 'custom':
                $start = $request->input('custom_start');
                $end = $request->input('custom_end');
                
                if ($start && $end) {
                    return [
                        'start' => Carbon::parse($start)->startOfDay(),
                        'end' => Carbon::parse($end)->endOfDay()
                    ];
                }
                return [
                    'start' => Carbon::now()->subDays(30)->startOfDay(),
                    'end' => Carbon::now()->endOfDay()
                ];
            
            default:
                return [
                    'start' => Carbon::now()->subDays(30)->startOfDay(),
                    'end' => Carbon::now()->endOfDay()
                ];
        }
    }

    /**
     * Get employee productivity data with SLA metrics
     */
    private function getEmployeeData($user, $startDate, $endDate)
    {
        try {
            $userEmployee = $user->employee;
            
            if (!$userEmployee) {
                Log::warning('User has no employee record', ['user_id' => $user->id]);
                return [];
            }

            // Get employees in the same business
            $employees = Employee::where('business_id', $userEmployee->business_id)
                ->where('id', '!=', $userEmployee->id)
                ->with(['user'])
                ->get();

            if ($employees->isEmpty()) {
                Log::info('No other employees found', ['business_id' => $userEmployee->business_id]);
                return [];
            }

            return $employees->map(function ($employee) use ($startDate, $endDate) {
                // Get task metrics with SLA tracking
                $taskMetrics = $this->getEmployeeTaskMetrics($employee->user_id, $startDate, $endDate);
                
                // Get ticket metrics with SLA tracking
                $ticketMetrics = $this->getEmployeeTicketMetrics($employee->user_id, $startDate, $endDate);
                
                // Calculate combined SLA compliance
                $slaCompliance = $this->calculateEmployeeSlaCompliance($taskMetrics, $ticketMetrics);
                
                // Calculate efficiency
                $efficiency = $this->calculateEmployeeEfficiency($employee->user_id, $startDate, $endDate);
                
                // Calculate productivity score
                $productivityScore = $this->calculateProductivityScore($taskMetrics, $ticketMetrics, $slaCompliance);
                
                return [
                    'id' => $employee->id,
                    'user_id' => $employee->user_id,
                    'employee_id' => $employee->employee_id,
                    'full_name' => $employee->user->name ?? $employee->full_name ?? 'Unknown',
                    'department' => $employee->department ?? 'Not Set',
                    'position' => $employee->position ?? 'Not Set',
                    
                    // Task metrics
                    'tasks_completed' => $taskMetrics['completed'],
                    'total_tasks' => $taskMetrics['total'],
                    'completed_tasks' => $taskMetrics['completed'],
                    'in_progress_tasks' => $taskMetrics['in_progress'],
                    'pending_tasks' => $taskMetrics['pending'],
                    'overdue_tasks' => $taskMetrics['overdue'],
                    'on_time_rate' => $taskMetrics['on_time_rate'],
                    'task_sla_compliance' => $taskMetrics['sla_compliance'],
                    
                    // Ticket metrics
                    'total_tickets' => $ticketMetrics['total'],
                    'resolved_tickets' => $ticketMetrics['resolved'],
                    'response_time_rate' => $ticketMetrics['response_sla_rate'],
                    'resolution_rate' => $ticketMetrics['resolution_sla_rate'],
                    'ticket_sla_compliance' => $ticketMetrics['overall_sla_compliance'],
                    
                    // Overall metrics
                    'sla_compliance' => $slaCompliance,
                    'efficiency_rate' => $efficiency,
                    'productivity_score' => $productivityScore,
                    'performance_level' => $this->getPerformanceLevel($productivityScore),
                    
                    // Time metrics
                    'hours_worked' => $this->calculateHoursWorked($employee->user_id, $startDate, $endDate),
                    'avg_completion_time' => $taskMetrics['avg_completion_time'],
                    'avg_response_time' => $ticketMetrics['avg_response_time'],
                    'avg_resolution_time' => $ticketMetrics['avg_resolution_time'],
                    
                    // Trend data
                    'trend' => $this->calculateTrend($employee->user_id, $startDate, $endDate),
                    'trend_percentage' => $this->calculateTrendPercentage($employee->user_id, $startDate, $endDate),
                    
                    // Period info
                    'period_start' => $startDate->toDateString(),
                    'period_end' => $endDate->toDateString()
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error('Failed to get employee data', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
            return [];
        }
    }

    /**
     * Get task metrics with SLA tracking - UPDATED
     */
    private function getEmployeeTaskMetrics($userId, $startDate, $endDate)
    {
        try {
            $allTasks = Task::where('assigned_to', $userId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $total = $allTasks->count();
            
            if ($total === 0) {
                return [
                    'total' => 0,
                    'completed' => 0,
                    'in_progress' => 0,
                    'pending' => 0,
                    'overdue' => 0,
                    'on_time' => 0,
                    'on_time_rate' => 0,
                    'sla_met' => 0,
                    'sla_compliance' => 0,
                    'avg_completion_time' => 0
                ];
            }

            // Completed tasks
            $completedTasks = $allTasks->where('status', 'completed');
            $completed = $completedTasks->count();
            
            // In progress
            $in_progress = $allTasks->where('status', 'in_progress')->count();
            
            // Pending
            $pending = $allTasks->whereIn('status', ['todo', 'pending'])->count();
            
            // Overdue (not completed and past deadline)
            $overdue = $allTasks->filter(function ($task) {
                return $task->deadline && 
                       Carbon::parse($task->deadline) < now() && 
                       !in_array($task->status, ['completed', 'cancelled']);
            })->count();

            // On-time completed (completed before deadline) - using completed_on_time field
            $on_time = $completedTasks->filter(function ($task) {
                return $task->completed_on_time === true || 
                       ($task->deadline && 
                        $task->completed_at && 
                        Carbon::parse($task->completed_at) <= Carbon::parse($task->deadline));
            })->count();

            // SLA compliance - using sla_breached field
            $sla_met = $completedTasks->filter(function ($task) {
                return $task->sla_breached === false;
            })->count();

            // Average completion time
            $avg_completion_time = 0;
            if ($completed > 0) {
                $total_time = $completedTasks->sum(function ($task) {
                    if ($task->actual_completion_time) {
                        return $task->actual_completion_time;
                    }
                    if ($task->completed_at && $task->created_at) {
                        return Carbon::parse($task->created_at)
                            ->diffInHours(Carbon::parse($task->completed_at));
                    }
                    return 0;
                });
                $avg_completion_time = round($total_time / $completed, 1);
            }

            return [
                'total' => $total,
                'completed' => $completed,
                'in_progress' => $in_progress,
                'pending' => $pending,
                'overdue' => $overdue,
                'on_time' => $on_time,
                'on_time_rate' => $this->calculatePercentage($on_time, $completed),
                'sla_met' => $sla_met,
                'sla_compliance' => $this->calculatePercentage($sla_met, $completed),
                'avg_completion_time' => $avg_completion_time
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get task metrics', [
                'user_id' => $userId, 
                'error' => $e->getMessage()
            ]);
            return [
                'total' => 0,
                'completed' => 0,
                'in_progress' => 0,
                'pending' => 0,
                'overdue' => 0,
                'on_time' => 0,
                'on_time_rate' => 0,
                'sla_met' => 0,
                'sla_compliance' => 0,
                'avg_completion_time' => 0
            ];
        }
    }

    /**
     * Get ticket metrics with SLA tracking - UPDATED
     */
    private function getEmployeeTicketMetrics($userId, $startDate, $endDate)
    {
        try {
            $tickets = Ticket::whereHas('assignedUsers', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

            $total = $tickets->count();
            
            if ($total === 0) {
                return [
                    'total' => 0,
                    'resolved' => 0,
                    'response_sla_met' => 0,
                    'response_sla_rate' => 0,
                    'resolution_sla_met' => 0,
                    'resolution_sla_rate' => 0,
                    'overall_sla_compliance' => 0,
                    'avg_response_time' => 0,
                    'avg_resolution_time' => 0
                ];
            }

            // Resolved tickets
            $resolvedTickets = $tickets->whereIn('status', ['resolved', 'closed']);
            $resolved = $resolvedTickets->count();
            
            // Response SLA compliance - using response_sla_breached field
            $response_sla_met = $tickets->filter(function ($ticket) {
                // Only count tickets that have a first response
                if (!$ticket->first_response_at) {
                    return false;
                }
                return $ticket->response_sla_breached === false;
            })->count();

            // Count tickets with first response for response rate calculation
            $tickets_with_response = $tickets->filter(function ($ticket) {
                return $ticket->first_response_at !== null;
            })->count();

            // Resolution SLA compliance - using resolution_sla_breached field
            $resolution_sla_met = $resolvedTickets->filter(function ($ticket) {
                return $ticket->resolution_sla_breached === false;
            })->count();

            // Average response time - using actual_response_time field
            $avg_response_time = $tickets->filter(function ($ticket) {
                return $ticket->actual_response_time !== null;
            })->avg('actual_response_time') ?? 0;

            // Average resolution time - using actual_resolution_time field
            $avg_resolution_time = $resolvedTickets->filter(function ($ticket) {
                return $ticket->actual_resolution_time !== null;
            })->avg('actual_resolution_time') ?? 0;

            // Overall SLA compliance (weighted average)
            $response_rate = $this->calculatePercentage($response_sla_met, $tickets_with_response);
            $resolution_rate = $this->calculatePercentage($resolution_sla_met, $resolved);
            $overall_sla = ($response_rate + $resolution_rate) / 2;

            return [
                'total' => $total,
                'resolved' => $resolved,
                'response_sla_met' => $response_sla_met,
                'response_sla_rate' => $response_rate,
                'resolution_sla_met' => $resolution_sla_met,
                'resolution_sla_rate' => $resolution_rate,
                'overall_sla_compliance' => round($overall_sla, 1),
                'avg_response_time' => round($avg_response_time, 1),
                'avg_resolution_time' => round($avg_resolution_time, 1)
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get ticket metrics', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [
                'total' => 0,
                'resolved' => 0,
                'response_sla_met' => 0,
                'response_sla_rate' => 0,
                'resolution_sla_met' => 0,
                'resolution_sla_rate' => 0,
                'overall_sla_compliance' => 0,
                'avg_response_time' => 0,
                'avg_resolution_time' => 0
            ];
        }
    }

    /**
     * Calculate combined SLA compliance
     */
    private function calculateEmployeeSlaCompliance($taskMetrics, $ticketMetrics)
    {
        $task_sla = $taskMetrics['sla_compliance'];
        $ticket_sla = $ticketMetrics['overall_sla_compliance'];
        
        // If both have data, weighted average (60% tasks, 40% tickets)
        if ($taskMetrics['completed'] > 0 && $ticketMetrics['total'] > 0) {
            return round(($task_sla * 0.6) + ($ticket_sla * 0.4), 1);
        }
        
        // If only tasks
        if ($taskMetrics['completed'] > 0) {
            return $task_sla;
        }
        
        // If only tickets
        if ($ticketMetrics['total'] > 0) {
            return $ticket_sla;
        }
        
        return 0;
    }

    /**
     * Calculate efficiency rate
     */
    private function calculateEmployeeEfficiency($userId, $startDate, $endDate)
    {
        try {
            $tasks = Task::where('assigned_to', $userId)
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$startDate, $endDate])
                ->whereNotNull('estimated_hours')
                ->whereNotNull('actual_hours')
                ->get();

            if ($tasks->isEmpty()) {
                return 0;
            }

            $totalEstimated = $tasks->sum('estimated_hours');
            $totalActual = $tasks->sum('actual_hours');
            
            if ($totalActual == 0) {
                return 100;
            }
            
            // Efficiency: if actual <= estimated, 100%; otherwise penalize
            $efficiency = min(100, ($totalEstimated / $totalActual) * 100);
            
            return round($efficiency, 1);

        } catch (\Exception $e) {
            Log::error('Failed to calculate efficiency', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Calculate overall productivity score
     */
    private function calculateProductivityScore($taskMetrics, $ticketMetrics, $slaCompliance)
    {
        if ($taskMetrics['total'] === 0 && $ticketMetrics['total'] === 0) {
            return 0;
        }

        $completionRate = $this->calculatePercentage($taskMetrics['completed'], $taskMetrics['total']);
        $onTimeRate = $taskMetrics['on_time_rate'];
        
        // Weighted average: 30% completion, 30% on-time, 40% SLA
        return round(
            ($completionRate * 0.3) + 
            ($onTimeRate * 0.3) + 
            ($slaCompliance * 0.4),
            1
        );
    }

    /**
     * Calculate hours worked
     */
    private function calculateHoursWorked($userId, $startDate, $endDate)
    {
        try {
            // Use actual_hours field if available, otherwise calculate
            $totalHours = Task::where('assigned_to', $userId)
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$startDate, $endDate])
                ->sum('actual_hours');

            if ($totalHours > 0) {
                return round($totalHours, 1);
            }

            // Fallback: calculate from timestamps
            $tasks = Task::where('assigned_to', $userId)
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$startDate, $endDate])
                ->get();

            $totalHours = $tasks->sum(function ($task) {
                if ($task->completed_at && $task->created_at) {
                    return Carbon::parse($task->created_at)
                        ->diffInHours(Carbon::parse($task->completed_at));
                }
                return 0;
            });

            return round($totalHours, 1);

        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculate trend
     */
    private function calculateTrend($userId, $startDate, $endDate)
    {
        try {
            $currentMetrics = $this->getEmployeeTaskMetrics($userId, $startDate, $endDate);
            $currentScore = $this->calculatePercentage($currentMetrics['completed'], $currentMetrics['total']);
            
            $periodDays = $startDate->diffInDays($endDate);
            $prevStart = $startDate->copy()->subDays($periodDays);
            $prevEnd = $startDate->copy();
            
            $prevMetrics = $this->getEmployeeTaskMetrics($userId, $prevStart, $prevEnd);
            $prevScore = $this->calculatePercentage($prevMetrics['completed'], $prevMetrics['total']);
            
            if ($currentScore > $prevScore + 5) {
                return 'up';
            } elseif ($currentScore < $prevScore - 5) {
                return 'down';
            }
            return 'neutral';

        } catch (\Exception $e) {
            return 'neutral';
        }
    }

    /**
     * Calculate trend percentage
     */
    private function calculateTrendPercentage($userId, $startDate, $endDate)
    {
        try {
            $currentMetrics = $this->getEmployeeTaskMetrics($userId, $startDate, $endDate);
            $currentScore = $this->calculatePercentage($currentMetrics['completed'], $currentMetrics['total']);
            
            $periodDays = $startDate->diffInDays($endDate);
            $prevStart = $startDate->copy()->subDays($periodDays);
            $prevEnd = $startDate->copy();
            
            $prevMetrics = $this->getEmployeeTaskMetrics($userId, $prevStart, $prevEnd);
            $prevScore = $this->calculatePercentage($prevMetrics['completed'], $prevMetrics['total']);
            
            if ($prevScore == 0) {
                return 0;
            }
            
            return round(abs((($currentScore - $prevScore) / $prevScore) * 100), 1);

        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculate team overview metrics
     */
    private function calculateOverview($employees, $startDate, $endDate)
    {
        if (empty($employees)) {
            return $this->getDefaultOverview();
        }

        $count = count($employees);
        $totalProductivity = array_sum(array_column($employees, 'productivity_score'));
        $totalTasksCompleted = array_sum(array_column($employees, 'completed_tasks'));
        $totalTasks = array_sum(array_column($employees, 'total_tasks'));
        $totalSlaCompliance = array_sum(array_column($employees, 'sla_compliance'));
        $totalOnTimeRate = array_sum(array_column($employees, 'on_time_rate'));
        $totalEfficiency = array_sum(array_column($employees, 'efficiency_rate'));
        $totalOverdue = array_sum(array_column($employees, 'overdue_tasks'));
        $totalPending = array_sum(array_column($employees, 'pending_tasks'));
        $totalHours = array_sum(array_column($employees, 'hours_worked'));
        
        $avgProductivity = $count > 0 ? round($totalProductivity / $count, 1) : 0;
        $avgSla = $count > 0 ? round($totalSlaCompliance / $count, 1) : 0;
        $avgOnTime = $count > 0 ? round($totalOnTimeRate / $count, 1) : 0;
        $avgEfficiency = $count > 0 ? round($totalEfficiency / $count, 1) : 0;

        return [
            'avgProductivityScore' => $avgProductivity,
            'totalTasksCompleted' => $totalTasksCompleted,
            'totalHoursWorked' => round($totalHours, 1),
            'completionRate' => $this->calculatePercentage($totalTasksCompleted, $totalTasks),
            'slaCompliance' => $avgSla,
            'onTimeRate' => $avgOnTime,
            'efficiencyRate' => $avgEfficiency,
            'tasksOnTime' => round($totalTasksCompleted * ($avgOnTime / 100)),
            'slaMet' => round($avgSla * $count),
            'totalSLA' => $count * 100,
            'overdueTasks' => $totalOverdue,
            'pendingTasks' => $totalPending,
            'completedTasks' => $totalTasksCompleted,
            'avgHoursPerTask' => $totalTasksCompleted > 0 ? round($totalHours / $totalTasksCompleted, 1) : 0,
            'trend' => $avgProductivity >= 80 ? 'up' : ($avgProductivity >= 60 ? 'neutral' : 'down'),
            'trend_percentage' => round(abs($avgProductivity - 70), 1)
        ];
    }

    /**
     * Calculate SLA details - UPDATED
     */
    private function calculateSlaDetails($user, $startDate, $endDate)
    {
        try {
            $userEmployee = $user->employee;
            if (!$userEmployee) {
                return $this->getDefaultSlaDetails();
            }

            $businessEmployeeUserIds = Employee::where('business_id', $userEmployee->business_id)
                ->pluck('user_id')
                ->toArray();

            // Get all tickets
            $tickets = Ticket::whereHas('assignedUsers', function($query) use ($businessEmployeeUserIds) {
                $query->whereIn('user_id', $businessEmployeeUserIds);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

            $totalTickets = $tickets->count();
            
            if ($totalTickets === 0) {
                return $this->getDefaultSlaDetails();
            }

            // Response time metrics using new fields
            $ticketsWithResponse = $tickets->filter(function ($ticket) {
                return $ticket->first_response_at !== null;
            });
            $responseTotal = $ticketsWithResponse->count();
            
            $responseTimeMet = $ticketsWithResponse->filter(function ($ticket) {
                return $ticket->response_sla_breached === false;
            })->count();

            $avgResponseTime = $ticketsWithResponse->avg('actual_response_time') ?? 0;

            // Resolution time metrics using new fields
            $resolvedTickets = $tickets->whereIn('status', ['resolved', 'closed']);
            $resolutionTotal = $resolvedTickets->count();
            
            $resolutionMet = $resolvedTickets->filter(function ($ticket) {
                return $ticket->resolution_sla_breached === false;
            })->count();

            $avgResolutionTime = $resolvedTickets->avg('actual_resolution_time') ?? 0;

            // Task completion metrics using new fields
            $tasks = Task::whereIn('assigned_to', $businessEmployeeUserIds)
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$startDate, $endDate])
                ->get();

            $completionTotal = $tasks->count();
            
            $completionMet = $tasks->filter(function ($task) {
                return $task->completed_on_time === true;
            })->count();

            $avgDelay = $tasks->filter(function ($task) {
                return $task->completed_on_time === false && $task->actual_completion_time;
            })->avg('actual_completion_time') ?? 0;

            return [
                'responseTimeRate' => $this->calculatePercentage($responseTimeMet, $responseTotal),
                'responseTimeMet' => $responseTimeMet,
                'responseTimeTotal' => $responseTotal,
                'resolutionRate' => $this->calculatePercentage($resolutionMet, $resolutionTotal),
                'resolutionMet' => $resolutionMet,
                'resolutionTotal' => $resolutionTotal,
                'completionRate' => $this->calculatePercentage($completionMet, $completionTotal),
                'completionMet' => $completionMet,
                'completionTotal' => $completionTotal,
                'avgResponseTime' => round($avgResponseTime, 1),
                'avgResolutionTime' => round($avgResolutionTime, 1),
                'avgDelay' => round($avgDelay, 1)
            ];

        } catch (\Exception $e) {
            Log::error('Failed to calculate SLA details', [
                'error' => $e->getMessage()
            ]);
            return $this->getDefaultSlaDetails();
        }
    }

    /**
     * Calculate task analysis - UPDATED
     */
    private function calculateTaskAnalysis($user, $startDate, $endDate)
    {
        try {
            $userEmployee = $user->employee;
            if (!$userEmployee) {
                return $this->getDefaultTaskAnalysis();
            }

            $businessEmployeeUserIds = Employee::where('business_id', $userEmployee->business_id)
                ->pluck('user_id')
                ->toArray();

            $tasks = Task::whereIn('assigned_to', $businessEmployeeUserIds)
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$startDate, $endDate])
                ->get();

            if ($tasks->isEmpty()) {
                return $this->getDefaultTaskAnalysis();
            }

            // On-time rate using completed_on_time field
            $onTime = $tasks->filter(function ($task) {
                return $task->completed_on_time === true;
            })->count();

            $onTimeRate = $this->calculatePercentage($onTime, $tasks->count());

            // Average completion time using actual_completion_time field
            $avgCompletionTime = $tasks->avg('actual_completion_time') ?? 0;

            // Quality score (inverse of reopen rate)
            $allTasks = Task::whereIn('assigned_to', $businessEmployeeUserIds)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $reopened = $allTasks->where('status', 'reopened')->count();
            $qualityScore = max(0, 100 - $this->calculatePercentage($reopened, $allTasks->count()));
            $reopenRate = $this->calculatePercentage($reopened, $allTasks->count());

            return [
                'onTimeRate' => $onTimeRate,
                'avgCompletionTime' => round($avgCompletionTime, 1),
                'qualityScore' => round($qualityScore, 1),
                'reopenRate' => $reopenRate,
                'onTimeTrend' => 'neutral',
                'onTimeChange' => 0,
                'completionTimeTrend' => 'neutral',
                'completionTimeChange' => 0,
                'qualityTrend' => 'neutral',
                'qualityChange' => 0,
                'reopenTrend' => 'neutral',
                'reopenChange' => 0
            ];

        } catch (\Exception $e) {
            Log::error('Failed to calculate task analysis', [
                'error' => $e->getMessage()
            ]);
            return $this->getDefaultTaskAnalysis();
        }
    }

    /**
     * Generate chart data - UPDATED
     */
    private function generateChartData($user, $startDate, $endDate)
    {
        try {
            $userEmployee = $user->employee;
            if (!$userEmployee) {
                return $this->getDefaultChartData();
            }

            $businessEmployeeUserIds = Employee::where('business_id', $userEmployee->business_id)
                ->pluck('user_id')
                ->toArray();

            $periodDays = $startDate->diffInDays($endDate);
            $numWeeks = max(1, ceil($periodDays / 7));
            
            $weeks = [];
            $slaData = [];
            $productivityData = [];
            $currentDate = $startDate->copy();
            
            while ($currentDate <= $endDate && count($weeks) < $numWeeks) {
                $weekEnd = min($currentDate->copy()->addDays(6), $endDate);
                $weeks[] = $currentDate->format('M d');
                
                // Task SLA compliance for this week
                $weekTasks = Task::whereIn('assigned_to', $businessEmployeeUserIds)
                    ->where('status', 'completed')
                    ->whereBetween('completed_at', [$currentDate, $weekEnd])
                    ->get();
                
                $weekSlaCompliant = $weekTasks->filter(function ($task) {
                    return $task->sla_breached === false;
                })->count();
                
                $slaData[] = $this->calculatePercentage($weekSlaCompliant, $weekTasks->count());
                
                // Productivity score
                $weekCompleted = $weekTasks->count();
                $weekTotal = Task::whereIn('assigned_to', $businessEmployeeUserIds)
                    ->whereBetween('created_at', [$currentDate, $weekEnd])
                    ->count();
                
                $productivityData[] = $this->calculatePercentage($weekCompleted, $weekTotal);
                
                $currentDate->addDays(7);
            }

            // Task distribution
            $allTasks = Task::whereIn('assigned_to', $businessEmployeeUserIds)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $onTimeTasks = $allTasks->filter(function ($task) {
                return $task->status === 'completed' && $task->completed_on_time === true;
            })->count();

            $lateTasks = $allTasks->filter(function ($task) {
                return $task->status === 'completed' && $task->completed_on_time === false;
            })->count();

            $inProgressTasks = $allTasks->where('status', 'in_progress')->count();
            
            $overdueTasks = $allTasks->filter(function ($task) {
                return $task->deadline && 
                       Carbon::parse($task->deadline) < now() && 
                       !in_array($task->status, ['completed', 'cancelled']);
            })->count();

            return [
                'sla_trend' => [
                    'labels' => $weeks,
                    'data' => $slaData
                ],
                'task_distribution' => [
                    'labels' => ['On Time', 'Completed Late', 'In Progress', 'Overdue'],
                    'data' => [$onTimeTasks, $lateTasks, $inProgressTasks, $overdueTasks]
                ],
                'weekly_performance' => [
                    'labels' => $weeks,
                    'completed' => array_fill(0, count($weeks), 0), // Would need week-by-week data
                    'sla_met' => $slaData
                ],
                'timeline' => [
                    'labels' => $weeks,
                    'productivity' => $productivityData,
                    'sla' => $slaData
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate chart data', [
                'error' => $e->getMessage()
            ]);
            return $this->getDefaultChartData();
        }
    }

    /**
     * Helper methods
     */
    private function calculatePercentage($numerator, $denominator, $decimals = 1)
    {
        if ($denominator == 0) {
            return 0;
        }
        return round(($numerator / $denominator) * 100, $decimals);
    }

    private function getPerformanceLevel($score)
    {
        if ($score >= 90) return 'excellent';
        if ($score >= 80) return 'good';
        if ($score >= 70) return 'average';
        if ($score >= 60) return 'needs_improvement';
        return 'poor';
    }

    private function getDefaultOverview()
    {
        return [
            'avgProductivityScore' => 0,
            'totalTasksCompleted' => 0,
            'totalHoursWorked' => 0,
            'completionRate' => 0,
            'slaCompliance' => 0,
            'onTimeRate' => 0,
            'efficiencyRate' => 0,
            'tasksOnTime' => 0,
            'slaMet' => 0,
            'totalSLA' => 0,
            'overdueTasks' => 0,
            'pendingTasks' => 0,
            'completedTasks' => 0,
            'avgHoursPerTask' => 0,
            'trend' => 'neutral',
            'trend_percentage' => 0
        ];
    }

    private function getDefaultSlaDetails()
    {
        return [
            'responseTimeRate' => 0,
            'responseTimeMet' => 0,
            'responseTimeTotal' => 0,
            'resolutionRate' => 0,
            'resolutionMet' => 0,
            'resolutionTotal' => 0,
            'completionRate' => 0,
            'completionMet' => 0,
            'completionTotal' => 0,
            'avgResponseTime' => 0,
            'avgResolutionTime' => 0,
            'avgDelay' => 0
        ];
    }

    private function getDefaultTaskAnalysis()
    {
        return [
            'onTimeRate' => 0,
            'avgCompletionTime' => 0,
            'qualityScore' => 0,
            'reopenRate' => 0,
            'onTimeTrend' => 'neutral',
            'onTimeChange' => 0,
            'completionTimeTrend' => 'neutral',
            'completionTimeChange' => 0,
            'qualityTrend' => 'neutral',
            'qualityChange' => 0,
            'reopenTrend' => 'neutral',
            'reopenChange' => 0
        ];
    }

    private function getDefaultChartData()
    {
        return [
            'sla_trend' => [
                'labels' => [],
                'data' => []
            ],
            'task_distribution' => [
                'labels' => [],
                'data' => []
            ],
            'weekly_performance' => [
                'labels' => [],
                'completed' => [],
                'sla_met' => []
            ],
            'timeline' => [
                'labels' => [],
                'productivity' => [],
                'sla' => []
            ]
        ];
    }

    /**
     * Alias methods
     */
    public function adminIndex(Request $request)
    {
        return $this->index($request);
    }

    public function managerIndex(Request $request)
    {
        return $this->index($request);
    }
    
    public function getEnhancedProductivity(Request $request)
    {
        return $this->index($request);
    }
}