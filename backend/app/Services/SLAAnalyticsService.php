<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SLAAnalyticsService
{
    /**
     * Get ticket SLA compliance metrics
     */
    public function getTicketSLAMetrics($businessId, $startDate = null, $endDate = null)
    {
        $query = Ticket::whereHas('user.employee', function($q) use ($businessId) {
            $q->where('business_id', $businessId);
        });

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $totalTickets = $query->count();
        
        // Response SLA metrics
        $ticketsWithResponse = (clone $query)->whereNotNull('first_response_at')->count();
        $responseSLAMet = (clone $query)
            ->whereNotNull('first_response_at')
            ->where('response_sla_breached', false)
            ->count();
        $responseSLABreached = (clone $query)->where('response_sla_breached', true)->count();
        
        // Resolution SLA metrics
        $resolvedTickets = (clone $query)->whereNotNull('resolved_at')->count();
        $resolutionSLAMet = (clone $query)
            ->whereNotNull('resolved_at')
            ->where('resolution_sla_breached', false)
            ->count();
        $resolutionSLABreached = (clone $query)->where('resolution_sla_breached', true)->count();

        // Average times
        $avgResponseTime = (clone $query)
            ->whereNotNull('actual_response_time')
            ->avg('actual_response_time');
        
        $avgResolutionTime = (clone $query)
            ->whereNotNull('actual_resolution_time')
            ->avg('actual_resolution_time');

        // At-risk tickets (active tickets with less than 25% time remaining)
        $atRiskTickets = (clone $query)
            ->whereNull('resolved_at')
            ->whereNotNull('resolution_sla_hours')
            ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, NOW()) > (resolution_sla_hours * 0.75)')
            ->count();

        return [
            'total_tickets' => $totalTickets,
            'response_sla' => [
                'total_with_response' => $ticketsWithResponse,
                'met' => $responseSLAMet,
                'breached' => $responseSLABreached,
                'compliance_rate' => $ticketsWithResponse > 0 
                    ? round(($responseSLAMet / $ticketsWithResponse) * 100, 2) 
                    : 0,
                'average_response_time' => round($avgResponseTime, 2),
            ],
            'resolution_sla' => [
                'total_resolved' => $resolvedTickets,
                'met' => $resolutionSLAMet,
                'breached' => $resolutionSLABreached,
                'compliance_rate' => $resolvedTickets > 0 
                    ? round(($resolutionSLAMet / $resolvedTickets) * 100, 2) 
                    : 0,
                'average_resolution_time' => round($avgResolutionTime, 2),
            ],
            'at_risk_tickets' => $atRiskTickets,
        ];
    }

    /**
     * Get task SLA compliance metrics
     */
    public function getTaskSLAMetrics($businessId, $startDate = null, $endDate = null)
    {
        $businessEmployeeUserIds = \App\Models\Employee::where('business_id', $businessId)
            ->pluck('user_id')
            ->toArray();

        $query = Task::where(function($q) use ($businessEmployeeUserIds) {
            $q->whereIn('assigned_to', $businessEmployeeUserIds)
              ->orWhereIn('created_by', $businessEmployeeUserIds);
        });

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $totalTasks = $query->count();
        $completedTasks = (clone $query)->where('status', 'completed')->count();
        
        // On-time completion
        $completedOnTime = (clone $query)
            ->where('status', 'completed')
            ->where('completed_on_time', true)
            ->count();
        
        // SLA metrics
        $slaBreached = (clone $query)->where('sla_breached', true)->count();
        $slaCompliant = (clone $query)
            ->where('status', 'completed')
            ->where('sla_breached', false)
            ->count();

        // Overdue tasks (not completed and past deadline)
        $overdueTasks = (clone $query)
            ->whereNull('completed_at')
            ->where('deadline', '<', now())
            ->count();

        // At-risk tasks (deadline within 24 hours)
        $atRiskTasks = (clone $query)
            ->whereNull('completed_at')
            ->where('deadline', '>', now())
            ->where('deadline', '<', now()->addHours(24))
            ->count();

        // Average completion time
        $avgCompletionTime = (clone $query)
            ->whereNotNull('actual_completion_time')
            ->avg('actual_completion_time');

        // Efficiency (estimated vs actual)
        $tasksWithEstimates = (clone $query)
            ->whereNotNull('estimated_hours')
            ->whereNotNull('actual_hours')
            ->get();

        $efficiencyRate = 0;
        if ($tasksWithEstimates->count() > 0) {
            $totalEstimated = $tasksWithEstimates->sum('estimated_hours');
            $totalActual = $tasksWithEstimates->sum('actual_hours');
            $efficiencyRate = $totalEstimated > 0 
                ? round(($totalEstimated / $totalActual) * 100, 2) 
                : 0;
        }

        return [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'completion_rate' => $totalTasks > 0 
                ? round(($completedTasks / $totalTasks) * 100, 2) 
                : 0,
            'on_time_completion' => [
                'count' => $completedOnTime,
                'rate' => $completedTasks > 0 
                    ? round(($completedOnTime / $completedTasks) * 100, 2) 
                    : 0,
            ],
            'sla_compliance' => [
                'compliant' => $slaCompliant,
                'breached' => $slaBreached,
                'rate' => $completedTasks > 0 
                    ? round(($slaCompliant / $completedTasks) * 100, 2) 
                    : 0,
            ],
            'overdue_tasks' => $overdueTasks,
            'at_risk_tasks' => $atRiskTasks,
            'average_completion_time' => round($avgCompletionTime, 2),
            'efficiency_rate' => $efficiencyRate,
        ];
    }

    /**
     * Get SLA trend data (weekly breakdown)
     */
    public function getSLATrend($businessId, $startDate, $endDate, $type = 'ticket')
    {
        $weeks = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($current <= $end) {
            $weekEnd = $current->copy()->addWeek();
            if ($weekEnd > $end) {
                $weekEnd = $end;
            }

            if ($type === 'ticket') {
                $metrics = $this->getTicketSLAMetrics($businessId, $current, $weekEnd);
                $complianceRate = $metrics['resolution_sla']['compliance_rate'];
            } else {
                $metrics = $this->getTaskSLAMetrics($businessId, $current, $weekEnd);
                $complianceRate = $metrics['sla_compliance']['rate'];
            }

            $weeks[] = [
                'week_start' => $current->format('Y-m-d'),
                'week_end' => $weekEnd->format('Y-m-d'),
                'compliance_rate' => $complianceRate,
            ];

            $current = $weekEnd->copy()->addDay();
        }

        return $weeks;
    }
}