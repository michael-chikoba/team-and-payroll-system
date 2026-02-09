<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Task extends Model
{
    // Add these to your existing model
    
    /**
     * Get the completion logs for this task.
     */
    public function completionLogs(): HasMany
    {
        return $this->hasMany(TaskCompletionLog::class);
    }

    /**
     * Get the latest completion log.
     */
    public function latestCompletionLog(): HasOne
    {
        return $this->hasOne(TaskCompletionLog::class)->latest();
    }

    /**
     * Get the completion time in hours.
     */
    public function getCompletionTimeAttribute(): ?float
    {
        if (!$this->completed_at || !$this->created_at) {
            return null;
        }
        
        return $this->created_at->diffInHours($this->completed_at);
    }

    /**
     * Check if task was completed on time.
     */
    public function getIsOnTimeAttribute(): ?bool
    {
        if (!$this->completed_at || !$this->deadline) {
            return null;
        }
        
        return $this->completed_at <= $this->deadline;
    }

    /**
     * Calculate the delay in hours.
     */
    public function getDelayHoursAttribute(): ?float
    {
        if (!$this->is_on_time || !$this->completed_at || !$this->deadline) {
            return null;
        }
        
        $delay = $this->completed_at->diffInHours($this->deadline, false);
        return max(0, $delay);
    }

    /**
     * Log task completion automatically.
     */
    public function logCompletion(User $user, array $data = []): TaskCompletionLog
    {
        return TaskCompletionLog::logCompletion(
            $this,
            $user,
            $this->completed_at ?? now(),
            $data['quality_score'] ?? null,
            $data['revisions_count'] ?? 0,
            $data['feedback_score'] ?? null,
            $data['tags'] ?? [],
            $data['notes'] ?? null
        );
    }

    /**
     * Get completion statistics.
     */
    public function getCompletionStatsAttribute(): array
    {
        $latestLog = $this->latestCompletionLog;
        
        if (!$latestLog) {
            return [
                'completed' => false,
                'on_time' => null,
                'delay_hours' => null,
                'quality_score' => null,
                'efficiency_score' => null,
                'performance_score' => null,
                'performance_level' => null,
            ];
        }
        
        return [
            'completed' => true,
            'on_time' => $latestLog->is_on_time,
            'delay_hours' => $latestLog->delay_hours,
            'quality_score' => $latestLog->quality_score,
            'efficiency_score' => $latestLog->efficiency_score,
            'performance_score' => $latestLog->performance_score,
            'performance_level' => $latestLog->performance_level,
            'tags' => $latestLog->tags,
        ];
    }

    /**
     * Get productivity metrics for this task.
     */
    public function getProductivityMetricsAttribute(): array
    {
        $stats = $this->completion_stats;
        
        if (!$stats['completed']) {
            return [
                'status' => 'pending',
                'estimated_completion' => $this->deadline,
                'days_remaining' => $this->deadline ? now()->diffInDays($this->deadline, false) : null,
                'priority_impact' => $this->calculatePriorityImpact(),
            ];
        }
        
        return [
            'status' => 'completed',
            'completion_data' => $stats,
            'sla_impact' => $this->calculateSlaImpact(),
            'productivity_contribution' => $this->calculateProductivityContribution(),
        ];
    }

    /**
     * Calculate priority impact.
     */
    private function calculatePriorityImpact(): float
    {
        $priorities = [
            'low' => 1,
            'moderate' => 2,
            'high' => 3,
            'critical' => 4,
        ];
        
        $priorityWeight = $priorities[$this->priority] ?? 1;
        $daysRemaining = $this->deadline ? now()->diffInDays($this->deadline, false) : 30;
        
        // Higher priority and fewer days = higher impact
        return ($priorityWeight * 10) / max(1, $daysRemaining);
    }

    /**
     * Calculate SLA impact.
     */
    private function calculateSlaImpact(): array
    {
        $stats = $this->completion_stats;
        
        if (!$stats['completed']) {
            return ['impact' => 0, 'reason' => 'Not completed'];
        }
        
        if ($stats['on_time']) {
            return ['impact' => 1, 'reason' => 'Completed on time'];
        }
        
        $delay = $stats['delay_hours'] ?? 0;
        
        if ($delay <= 24) {
            return ['impact' => 0.5, 'reason' => 'Minor delay'];
        }
        
        if ($delay <= 72) {
            return ['impact' => 0.2, 'reason' => 'Moderate delay'];
        }
        
        return ['impact' => 0, 'reason' => 'Major delay'];
    }

    /**
     * Calculate productivity contribution.
     */
    private function calculateProductivityContribution(): float
    {
        $stats = $this->completion_stats;
        
        if (!$stats['completed']) {
            return 0;
        }
        
        $baseScore = $stats['performance_score'] ?? 50;
        $priorityWeight = $this->calculatePriorityWeight();
        $complexityWeight = $this->complexity_level === 'high' ? 1.5 : 1;
        
        return ($baseScore * $priorityWeight * $complexityWeight) / 100;
    }

    /**
     * Calculate priority weight.
     */
    private function calculatePriorityWeight(): float
    {
        $weights = [
            'low' => 0.8,
            'moderate' => 1.0,
            'high' => 1.2,
            'critical' => 1.5,
        ];
        
        return $weights[$this->priority] ?? 1.0;
    }
}