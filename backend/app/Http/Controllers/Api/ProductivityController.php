<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Task;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProductivityController extends Controller
{
    /**
     * Get productivity data with proper filtering
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

            Log::info('Fetching productivity data', [
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
                // Fallback to last 30 days if custom dates not provided
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
     * Get employee productivity data with date filtering
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
                // Get task counts with date filtering
                $taskCounts = $this->getEmployeeTaskCounts($employee->user_id, $startDate, $endDate);
                
                // Get ticket metrics
                $ticketMetrics = $this->getEmployeeTicketMetrics($employee->user_id, $startDate, $endDate);
                
                // Calculate SLA compliance
                $slaCompliance = $this->calculateEmployeeSlaCompliance($employee->user_id, $startDate, $endDate);
                
                // Calculate efficiency
                $efficiency = $this->calculateEmployeeEfficiency($employee->user_id, $startDate, $endDate);
                
                // Calculate productivity score
                $productivityScore = $this->calculateProductivityScore($taskCounts, $ticketMetrics, $slaCompliance);
                
                return [
                    'id' => $employee->id,
                    'user_id' => $employee->user_id,
                    'employee_id' => $employee->employee_id,
                    'full_name' => $employee->user->name ?? $employee->full_name ?? 'Unknown',
                    'department' => $employee->department ?? 'Not Set',
                    'position' => $employee->position ?? 'Not Set',
                    'tasks_completed' => $taskCounts['completed'],
                    'total_tasks' => $taskCounts['total'],
                    'completed_tasks' => $taskCounts['completed'],
                    'in_progress_tasks' => $taskCounts['in_progress'],
                    'pending_tasks' => $taskCounts['pending'],
                    'overdue_tasks' => $taskCounts['overdue'],
                    'on_time_rate' => $this->calculatePercentage($taskCounts['on_time'], $taskCounts['completed']),
                    'sla_compliance' => $slaCompliance,
                    'efficiency_rate' => $efficiency,
                    'productivity_score' => $productivityScore,
                    'performance_level' => $this->getPerformanceLevel($productivityScore),
                    'hours_worked' => $this->calculateHoursWorked($employee->user_id, $startDate, $endDate),
                    'avg_completion_time' => $this->calculateAvgCompletionTime($employee->user_id, $startDate, $endDate),
                    'response_time_rate' => $ticketMetrics['response_rate'],
                    'resolution_rate' => $ticketMetrics['resolution_rate'],
                    'trend' => $this->calculateTrend($employee->user_id, $startDate, $endDate),
                    'trend_percentage' => $this->calculateTrendPercentage($employee->user_id, $startDate, $endDate),
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
     * Get task counts with date filtering
     */
    private function getEmployeeTaskCounts($userId, $startDate, $endDate)
    {
        try {
            $tasks = Task::where('assigned_to', $userId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $total = $tasks->count();
            $completed = $tasks->where('status', 'completed')->count();
            $in_progress = $tasks->where('status', 'in_progress')->count();
            $pending = $tasks->whereIn('status', ['todo', 'pending'])->count();
            
            // Calculate overdue tasks
            $overdue = $tasks->filter(function ($task) {
                return $task->deadline && 
                       Carbon::parse($task->deadline) < now() && 
                       !in_array($task->status, ['completed', 'cancelled']);
            })->count();

            // Calculate on-time completed tasks
            $on_time = $tasks->filter(function ($task) {
                return $task->status === 'completed' && 
                       $task->completed_at && 
                       $task->deadline && 
                       Carbon::parse($task->completed_at) <= Carbon::parse($task->deadline);
            })->count();

            return [
                'total' => $total,
                'completed' => $completed,
                'in_progress' => $in_progress,
                'pending' => $pending,
                'overdue' => $overdue,
                'on_time' => $on_time
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get task counts', [
                'user_id' => $userId, 
                'error' => $e->getMessage()
            ]);
            return [
                'total' => 0,
                'completed' => 0,
                'in_progress' => 0,
                'pending' => 0,
                'overdue' => 0,
                'on_time' => 0
            ];
        }
    }

    /**
     * Get ticket metrics for an employee
     */
    private function getEmployeeTicketMetrics($userId, $startDate, $endDate)
    {
        try {
            $tickets = Ticket::where('assigned_to', $userId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $total = $tickets->count();
            
            if ($total === 0) {
                return [
                    'total' => 0,
                    'resolved' => 0,
                    'response_rate' => 0,
                    'resolution_rate' => 0
                ];
            }

            $resolved = $tickets->where('status', 'resolved')->count();
            
            // Calculate response time compliance (responded within SLA)
            $responseOnTime = $tickets->filter(function ($ticket) {
                if (!$ticket->first_response_at || !$ticket->response_sla_minutes) {
                    return false;
                }
                
                $responseTime = Carbon::parse($ticket->created_at)
                    ->diffInMinutes(Carbon::parse($ticket->first_response_at));
                
                return $responseTime <= $ticket->response_sla_minutes;
            })->count();

            // Calculate resolution time compliance
            $resolvedOnTime = $tickets->filter(function ($ticket) {
                if ($ticket->status !== 'resolved' || !$ticket->resolved_at || !$ticket->resolution_sla_minutes) {
                    return false;
                }
                
                $resolutionTime = Carbon::parse($ticket->created_at)
                    ->diffInMinutes(Carbon::parse($ticket->resolved_at));
                
                return $resolutionTime <= $ticket->resolution_sla_minutes;
            })->count();

            return [
                'total' => $total,
                'resolved' => $resolved,
                'response_rate' => $this->calculatePercentage($responseOnTime, $total),
                'resolution_rate' => $this->calculatePercentage($resolvedOnTime, $resolved)
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get ticket metrics', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [
                'total' => 0,
                'resolved' => 0,
                'response_rate' => 0,
                'resolution_rate' => 0
            ];
        }
    }

    /**
     * Calculate SLA compliance for an employee
     */
    private function calculateEmployeeSlaCompliance($userId, $startDate, $endDate)
    {
        try {
            $taskCounts = $this->getEmployeeTaskCounts($userId, $startDate, $endDate);
            $ticketMetrics = $this->getEmployeeTicketMetrics($userId, $startDate, $endDate);
            
            // Weighted average of task on-time rate and ticket metrics
            $taskOnTimeRate = $this->calculatePercentage($taskCounts['on_time'], $taskCounts['completed']);
            $avgTicketRate = ($ticketMetrics['response_rate'] + $ticketMetrics['resolution_rate']) / 2;
            
            // 60% weight to tasks, 40% to tickets
            return round(($taskOnTimeRate * 0.6) + ($avgTicketRate * 0.4), 1);

        } catch (\Exception $e) {
            Log::error('Failed to calculate SLA compliance', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
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
                ->whereNotNull('completed_at')
                ->whereNotNull('created_at')
                ->get();

            if ($tasks->isEmpty()) {
                return 0;
            }

            // Calculate average time to complete vs estimated time
            $efficiencyScores = $tasks->map(function ($task) {
                $actualTime = Carbon::parse($task->created_at)
                    ->diffInHours(Carbon::parse($task->completed_at));
                
                // Assume 8 hours as default estimated time if not set
                $estimatedTime = $task->estimated_hours ?? 8;
                
                if ($estimatedTime == 0) {
                    return 100;
                }
                
                // Calculate efficiency (100% if completed in estimated time or less)
                $efficiency = min(100, ($estimatedTime / max(1, $actualTime)) * 100);
                return $efficiency;
            });

            return round($efficiencyScores->average(), 1);

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
    private function calculateProductivityScore($taskCounts, $ticketMetrics, $slaCompliance)
    {
        if ($taskCounts['total'] === 0 && $ticketMetrics['total'] === 0) {
            return 0;
        }

        $completionRate = $this->calculatePercentage($taskCounts['completed'], $taskCounts['total']);
        $onTimeRate = $this->calculatePercentage($taskCounts['on_time'], $taskCounts['completed']);
        
        // Weighted average: 30% completion, 30% on-time, 40% SLA
        return round(
            ($completionRate * 0.3) + 
            ($onTimeRate * 0.3) + 
            ($slaCompliance * 0.4),
            1
        );
    }

    /**
     * Calculate hours worked (based on task completion times)
     */
    private function calculateHoursWorked($userId, $startDate, $endDate)
    {
        try {
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
     * Calculate average completion time
     */
    private function calculateAvgCompletionTime($userId, $startDate, $endDate)
    {
        try {
            $tasks = Task::where('assigned_to', $userId)
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$startDate, $endDate])
                ->whereNotNull('completed_at')
                ->whereNotNull('created_at')
                ->get();

            if ($tasks->isEmpty()) {
                return 0;
            }

            $totalHours = $tasks->sum(function ($task) {
                return Carbon::parse($task->created_at)
                    ->diffInHours(Carbon::parse($task->completed_at));
            });

            return round($totalHours / $tasks->count(), 1);

        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculate trend (up/down/neutral)
     */
    private function calculateTrend($userId, $startDate, $endDate)
    {
        try {
            // Get current period score
            $currentCounts = $this->getEmployeeTaskCounts($userId, $startDate, $endDate);
            $currentScore = $this->calculatePercentage($currentCounts['completed'], $currentCounts['total']);
            
            // Get previous period score
            $periodDays = $startDate->diffInDays($endDate);
            $prevStart = $startDate->copy()->subDays($periodDays);
            $prevEnd = $startDate->copy();
            
            $prevCounts = $this->getEmployeeTaskCounts($userId, $prevStart, $prevEnd);
            $prevScore = $this->calculatePercentage($prevCounts['completed'], $prevCounts['total']);
            
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
            $currentCounts = $this->getEmployeeTaskCounts($userId, $startDate, $endDate);
            $currentScore = $this->calculatePercentage($currentCounts['completed'], $currentCounts['total']);
            
            $periodDays = $startDate->diffInDays($endDate);
            $prevStart = $startDate->copy()->subDays($periodDays);
            $prevEnd = $startDate->copy();
            
            $prevCounts = $this->getEmployeeTaskCounts($userId, $prevStart, $prevEnd);
            $prevScore = $this->calculatePercentage($prevCounts['completed'], $prevCounts['total']);
            
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

        $totalProductivity = array_sum(array_column($employees, 'productivity_score'));
        $totalTasksCompleted = array_sum(array_column($employees, 'completed_tasks'));
        $totalTasks = array_sum(array_column($employees, 'total_tasks'));
        $totalSlaCompliance = array_sum(array_column($employees, 'sla_compliance'));
        $totalOnTimeRate = array_sum(array_column($employees, 'on_time_rate'));
        $totalEfficiency = array_sum(array_column($employees, 'efficiency_rate'));
        $totalOverdue = array_sum(array_column($employees, 'overdue_tasks'));
        $totalPending = array_sum(array_column($employees, 'pending_tasks'));
        $totalHours = array_sum(array_column($employees, 'hours_worked'));
        
        $count = count($employees);
        $avgProductivity = round($totalProductivity / $count, 1);
        $avgSla = round($totalSlaCompliance / $count, 1);
        $avgOnTime = round($totalOnTimeRate / $count, 1);
        $avgEfficiency = round($totalEfficiency / $count, 1);

        return [
            'avgProductivityScore' => $avgProductivity,
            'totalTasksCompleted' => $totalTasksCompleted,
            'totalHoursWorked' => $totalHours,
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
            'trend_percentage' => round(($avgProductivity - 70) / 10, 1)
        ];
    }

    /**
     * Calculate SLA details across all employees
     */
    private function calculateSlaDetails($user, $startDate, $endDate)
    {
        try {
            $userEmployee = $user->employee;
            if (!$userEmployee) {
                return $this->getDefaultSlaDetails();
            }

            // Get all tickets for the business in date range
            $tickets = Ticket::whereHas('assignedUser.employee', function($query) use ($userEmployee) {
                $query->where('business_id', $userEmployee->business_id);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

            $totalTickets = $tickets->count();
            
            if ($totalTickets === 0) {
                return $this->getDefaultSlaDetails();
            }

            // Response time metrics
            $responseTimeMet = $tickets->filter(function ($ticket) {
                if (!$ticket->first_response_at || !$ticket->response_sla_minutes) {
                    return false;
                }
                $responseTime = Carbon::parse($ticket->created_at)
                    ->diffInMinutes(Carbon::parse($ticket->first_response_at));
                return $responseTime <= $ticket->response_sla_minutes;
            })->count();

            $avgResponseTime = $tickets->filter(function ($ticket) {
                return $ticket->first_response_at;
            })->avg(function ($ticket) {
                return Carbon::parse($ticket->created_at)
                    ->diffInMinutes(Carbon::parse($ticket->first_response_at)) / 60;
            });

            // Resolution time metrics
            $resolvedTickets = $tickets->where('status', 'resolved');
            $resolutionTotal = $resolvedTickets->count();
            
            $resolutionMet = $resolvedTickets->filter(function ($ticket) {
                if (!$ticket->resolved_at || !$ticket->resolution_sla_minutes) {
                    return false;
                }
                $resolutionTime = Carbon::parse($ticket->created_at)
                    ->diffInMinutes(Carbon::parse($ticket->resolved_at));
                return $resolutionTime <= $ticket->resolution_sla_minutes;
            })->count();

            $avgResolutionTime = $resolvedTickets->filter(function ($ticket) {
                return $ticket->resolved_at;
            })->avg(function ($ticket) {
                return Carbon::parse($ticket->created_at)
                    ->diffInMinutes(Carbon::parse($ticket->resolved_at)) / 60;
            });

            // Task completion metrics
            $tasks = Task::whereHas('assignedUser.employee', function($query) use ($userEmployee) {
                $query->where('business_id', $userEmployee->business_id);
            })
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->get();

            $completionTotal = $tasks->count();
            
            $completionMet = $tasks->filter(function ($task) {
                return $task->deadline && 
                       $task->completed_at && 
                       Carbon::parse($task->completed_at) <= Carbon::parse($task->deadline);
            })->count();

            $avgDelay = $tasks->filter(function ($task) {
                return $task->deadline && 
                       $task->completed_at && 
                       Carbon::parse($task->completed_at) > Carbon::parse($task->deadline);
            })->avg(function ($task) {
                return Carbon::parse($task->deadline)
                    ->diffInHours(Carbon::parse($task->completed_at));
            });

            return [
                'responseTimeRate' => $this->calculatePercentage($responseTimeMet, $totalTickets),
                'responseTimeMet' => $responseTimeMet,
                'responseTimeTotal' => $totalTickets,
                'resolutionRate' => $this->calculatePercentage($resolutionMet, $resolutionTotal),
                'resolutionMet' => $resolutionMet,
                'resolutionTotal' => $resolutionTotal,
                'completionRate' => $this->calculatePercentage($completionMet, $completionTotal),
                'completionMet' => $completionMet,
                'completionTotal' => $completionTotal,
                'avgResponseTime' => round($avgResponseTime ?? 0, 1),
                'avgResolutionTime' => round($avgResolutionTime ?? 0, 1),
                'avgDelay' => round($avgDelay ?? 0, 1)
            ];

        } catch (\Exception $e) {
            Log::error('Failed to calculate SLA details', [
                'error' => $e->getMessage()
            ]);
            return $this->getDefaultSlaDetails();
        }
    }

    /**
     * Calculate task analysis metrics
     */
    private function calculateTaskAnalysis($user, $startDate, $endDate)
    {
        try {
            $userEmployee = $user->employee;
            if (!$userEmployee) {
                return $this->getDefaultTaskAnalysis();
            }

            $tasks = Task::whereHas('assignedUser.employee', function($query) use ($userEmployee) {
                $query->where('business_id', $userEmployee->business_id);
            })
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->get();

            if ($tasks->isEmpty()) {
                return $this->getDefaultTaskAnalysis();
            }

            // On-time rate
            $onTime = $tasks->filter(function ($task) {
                return $task->deadline && 
                       Carbon::parse($task->completed_at) <= Carbon::parse($task->deadline);
            })->count();

            $onTimeRate = $this->calculatePercentage($onTime, $tasks->count());

            // Average completion time
            $avgCompletionTime = $tasks->avg(function ($task) {
                return Carbon::parse($task->created_at)
                    ->diffInHours(Carbon::parse($task->completed_at));
            });

            // Quality score (based on reopening)
            $reopened = Task::whereHas('assignedUser.employee', function($query) use ($userEmployee) {
                $query->where('business_id', $userEmployee->business_id);
            })
            ->where('reopened', true)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

            $qualityScore = max(0, 100 - ($this->calculatePercentage($reopened, $tasks->count())));
            $reopenRate = $this->calculatePercentage($reopened, $tasks->count());

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
     * Generate chart data
     */
    private function generateChartData($user, $startDate, $endDate)
    {
        try {
            $userEmployee = $user->employee;
            if (!$userEmployee) {
                return $this->getDefaultChartData();
            }

            // SLA Trend (weekly)
            $weeks = [];
            $slaData = [];
            $currentDate = $startDate->copy();
            
            while ($currentDate <= $endDate) {
                $weekEnd = min($currentDate->copy()->addDays(6), $endDate);
                $weeks[] = $currentDate->format('M d');
                
                $weekTasks = Task::whereHas('assignedUser.employee', function($query) use ($userEmployee) {
                    $query->where('business_id', $userEmployee->business_id);
                })
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$currentDate, $weekEnd])
                ->get();
                
                $weekOnTime = $weekTasks->filter(function ($task) {
                    return $task->deadline && 
                           Carbon::parse($task->completed_at) <= Carbon::parse($task->deadline);
                })->count();
                
                $slaData[] = $this->calculatePercentage($weekOnTime, $weekTasks->count());
                $currentDate->addDays(7);
            }

            // Task Distribution
            $allTasks = Task::whereHas('assignedUser.employee', function($query) use ($userEmployee) {
                $query->where('business_id', $userEmployee->business_id);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

            $onTimeTasks = $allTasks->filter(function ($task) {
                return $task->status === 'completed' && 
                       $task->deadline && 
                       $task->completed_at &&
                       Carbon::parse($task->completed_at) <= Carbon::parse($task->deadline);
            })->count();

            $lateTasks = $allTasks->filter(function ($task) {
                return $task->status === 'completed' && 
                       $task->deadline && 
                       $task->completed_at &&
                       Carbon::parse($task->completed_at) > Carbon::parse($task->deadline);
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
                    'completed' => array_fill(0, count($weeks), 0), // Would need more complex logic
                    'sla_met' => array_fill(0, count($weeks), 0)
                ],
                'timeline' => [
                    'labels' => $weeks,
                    'productivity' => $slaData,
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
     * Helper method to calculate percentage safely
     */
    private function calculatePercentage($numerator, $denominator, $decimals = 1)
    {
        if ($denominator == 0) {
            return 0;
        }
        return round(($numerator / $denominator) * 100, $decimals);
    }

    /**
     * Get performance level based on score
     */
    private function getPerformanceLevel($score)
    {
        if ($score >= 90) return 'excellent';
        if ($score >= 80) return 'good';
        if ($score >= 70) return 'average';
        if ($score >= 60) return 'needs_improvement';
        return 'poor';
    }

    /**
     * Default empty structures
     */
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
     * Alias methods for different endpoints
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