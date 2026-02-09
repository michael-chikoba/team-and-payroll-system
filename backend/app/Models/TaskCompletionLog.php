<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TaskCompletionLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'task_id',
        'user_id',
        'completed_at',
        'deadline',
        'estimated_hours',
        'actual_hours',
        'completion_time_hours',
        'is_on_time',
        'quality_score',
        'complexity_level',
        'dependencies_met',
        'revisions_count',
        'feedback_score',
        'notes',
        'tags',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'completed_at' => 'datetime',
        'deadline' => 'datetime',
        'is_on_time' => 'boolean',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'completion_time_hours' => 'decimal:2',
        'quality_score' => 'decimal:3,2',
        'dependencies_met' => 'boolean',
        'revisions_count' => 'integer',
        'feedback_score' => 'decimal:3,2',
        'tags' => 'array',
    ];

    /**
     * Get the task associated with this log.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user who completed the task.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include on-time completions.
     */
    public function scopeOnTime($query)
    {
        return $query->where('is_on_time', true);
    }

    /**
     * Scope a query to only include late completions.
     */
    public function scopeLate($query)
    {
        return $query->where('is_on_time', false);
    }

    /**
     * Scope a query to only include logs within a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('completed_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include logs for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include high quality completions.
     */
    public function scopeHighQuality($query, $threshold = 0.8)
    {
        return $query->where('quality_score', '>=', $threshold);
    }

    /**
     * Calculate efficiency score.
     */
    public function getEfficiencyScoreAttribute(): float
    {
        if ($this->estimated_hours == 0) {
            return 100;
        }
        
        $efficiency = ($this->estimated_hours / $this->actual_hours) * 100;
        return min(100, max(0, $efficiency));
    }

    /**
     * Calculate delay in hours.
     */
    public function getDelayHoursAttribute(): float
    {
        if ($this->is_on_time || !$this->completed_at || !$this->deadline) {
            return 0;
        }
        
        $delay = $this->completed_at->diffInHours($this->deadline, false);
        return max(0, $delay);
    }

    /**
     * Calculate delay percentage.
     */
    public function getDelayPercentageAttribute(): float
    {
        if ($this->estimated_hours == 0 || $this->is_on_time) {
            return 0;
        }
        
        return ($this->delay_hours / $this->estimated_hours) * 100;
    }

    /**
     * Get overall performance score.
     */
    public function getPerformanceScoreAttribute(): float
    {
        $timelinessWeight = 0.4;
        $efficiencyWeight = 0.3;
        $qualityWeight = 0.3;
        
        $timelinessScore = $this->is_on_time ? 100 : max(0, 100 - $this->delay_percentage);
        $efficiencyScore = $this->efficiency_score;
        $qualityScore = $this->quality_score ? $this->quality_score * 100 : 80; // Default if not set
        
        return round(
            ($timelinessScore * $timelinessWeight) +
            ($efficiencyScore * $efficiencyWeight) +
            ($qualityScore * $qualityWeight),
            2
        );
    }

    /**
     * Get performance level.
     */
    public function getPerformanceLevelAttribute(): string
    {
        $score = $this->performance_score;
        
        if ($score >= 90) return 'excellent';
        if ($score >= 80) return 'good';
        if ($score >= 70) return 'average';
        if ($score >= 60) return 'needs_improvement';
        return 'poor';
    }

    /**
     * Get status color based on performance.
     */
    public function getStatusColorAttribute(): string
    {
        $level = $this->performance_level;
        
        $colors = [
            'excellent' => 'success',
            'good' => 'primary',
            'average' => 'warning',
            'needs_improvement' => 'info',
            'poor' => 'danger',
        ];
        
        return $colors[$level] ?? 'secondary';
    }

    /**
     * Get status icon.
     */
    public function getStatusIconAttribute(): string
    {
        $level = $this->performance_level;
        
        $icons = [
            'excellent' => '🏆',
            'good' => '✅',
            'average' => '⚡',
            'needs_improvement' => '⚠️',
            'poor' => '❌',
        ];
        
        return $icons[$level] ?? '📝';
    }

    /**
     * Log a new task completion.
     */
    public static function logCompletion(
        Task $task,
        User $user,
        Carbon $completedAt,
        ?float $qualityScore = null,
        ?int $revisionsCount = 0,
        ?float $feedbackScore = null,
        ?array $tags = [],
        ?string $notes = null
    ): self {
        $deadline = $task->deadline;
        $estimatedHours = $task->estimated_hours ?? 0;
        $actualHours = $task->completion_time_hours ?? 0;
        
        if ($actualHours == 0 && $task->created_at && $completedAt) {
            $actualHours = $task->created_at->diffInHours($completedAt);
        }
        
        $completionTimeHours = $actualHours;
        $isOnTime = $deadline ? $completedAt <= $deadline : true;
        
        return self::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'completed_at' => $completedAt,
            'deadline' => $deadline,
            'estimated_hours' => $estimatedHours,
            'actual_hours' => $actualHours,
            'completion_time_hours' => $completionTimeHours,
            'is_on_time' => $isOnTime,
            'quality_score' => $qualityScore,
            'complexity_level' => $task->complexity_level ?? 'medium',
            'dependencies_met' => $task->dependencies_met ?? true,
            'revisions_count' => $revisionsCount,
            'feedback_score' => $feedbackScore,
            'notes' => $notes,
            'tags' => $tags,
        ]);
    }

    /**
     * Get statistics for a user.
     */
    public static function getUserStatistics(int $userId, string $period = 'last_30_days'): array
    {
        $query = self::forUser($userId);
        
        // Set date range based on period
        $endDate = now();
        switch ($period) {
            case 'last_7_days':
                $startDate = now()->subDays(7);
                break;
            case 'last_30_days':
                $startDate = now()->subDays(30);
                break;
            case 'this_month':
                $startDate = now()->startOfMonth();
                break;
            case 'last_month':
                $startDate = now()->subMonth()->startOfMonth();
                $endDate = now()->subMonth()->endOfMonth();
                break;
            default:
                $startDate = now()->subDays(30);
        }
        
        $query->betweenDates($startDate, $endDate);
        
        $total = $query->count();
        $onTime = $query->clone()->onTime()->count();
        $late = $query->clone()->late()->count();
        
        $avgCompletionTime = $query->clone()->avg('completion_time_hours');
        $avgQualityScore = $query->clone()->avg('quality_score');
        $avgEfficiency = $query->clone()->avg('actual_hours');
        $totalHours = $query->clone()->sum('actual_hours');
        
        $performanceScores = $query->clone()
            ->get()
            ->map(function ($log) {
                return $log->performance_score;
            })
            ->toArray();
        
        $avgPerformance = count($performanceScores) > 0 
            ? array_sum($performanceScores) / count($performanceScores) 
            : 0;
        
        return [
            'total_tasks' => $total,
            'on_time_tasks' => $onTime,
            'late_tasks' => $late,
            'on_time_rate' => $total > 0 ? round(($onTime / $total) * 100, 2) : 0,
            'avg_completion_time' => round($avgCompletionTime, 2),
            'avg_quality_score' => round($avgQualityScore, 3),
            'avg_efficiency' => round($avgEfficiency, 2),
            'total_hours_worked' => round($totalHours, 2),
            'avg_performance_score' => round($avgPerformance, 2),
            'performance_level' => self::getPerformanceLevelFromScore($avgPerformance),
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ];
    }

    /**
     * Get performance level from score.
     */
    private static function getPerformanceLevelFromScore(float $score): string
    {
        if ($score >= 90) return 'excellent';
        if ($score >= 80) return 'good';
        if ($score >= 70) return 'average';
        if ($score >= 60) return 'needs_improvement';
        return 'poor';
    }

    /**
     * Get trend data for a user.
     */
    public static function getUserTrend(int $userId, int $periods = 4): array
    {
        $trends = [];
        $endDate = now();
        
        for ($i = $periods - 1; $i >= 0; $i--) {
            $periodEnd = $endDate->copy()->subWeeks($i);
            $periodStart = $periodEnd->copy()->subWeek();
            
            $stats = self::getUserStatistics($userId, [
                'start' => $periodStart,
                'end' => $periodEnd,
            ]);
            
            $trends[] = [
                'period' => "Week " . ($periods - $i),
                'start_date' => $periodStart->toDateString(),
                'end_date' => $periodEnd->toDateString(),
                'on_time_rate' => $stats['on_time_rate'],
                'avg_performance_score' => $stats['avg_performance_score'],
                'tasks_completed' => $stats['total_tasks'],
            ];
        }
        
        return $trends;
    }

    /**
     * Get leaderboard data.
     */
    public static function getLeaderboard(string $period = 'last_30_days', int $limit = 10): array
    {
        $query = self::query();
        
        // Set date range
        $endDate = now();
        $startDate = match ($period) {
            'last_7_days' => now()->subDays(7),
            'last_30_days' => now()->subDays(30),
            'this_month' => now()->startOfMonth(),
            'last_month' => now()->subMonth()->startOfMonth(),
            default => now()->subDays(30),
        };
        
        $query->betweenDates($startDate, $endDate);
        
        return $query->selectRaw('
                user_id,
                COUNT(*) as total_tasks,
                SUM(CASE WHEN is_on_time = 1 THEN 1 ELSE 0 END) as on_time_tasks,
                AVG(quality_score) as avg_quality,
                AVG(actual_hours) as avg_hours_per_task,
                SUM(actual_hours) as total_hours
            ')
            ->groupBy('user_id')
            ->with('user')
            ->orderByRaw('(SUM(CASE WHEN is_on_time = 1 THEN 1 ELSE 0 END) / COUNT(*)) DESC')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $onTimeRate = $item->total_tasks > 0 
                    ? round(($item->on_time_tasks / $item->total_tasks) * 100, 2) 
                    : 0;
                
                return [
                    'user_id' => $item->user_id,
                    'user_name' => $item->user->name ?? 'Unknown',
                    'total_tasks' => $item->total_tasks,
                    'on_time_tasks' => $item->on_time_tasks,
                    'on_time_rate' => $onTimeRate,
                    'avg_quality' => round($item->avg_quality, 3),
                    'avg_hours_per_task' => round($item->avg_hours_per_task, 2),
                    'total_hours' => round($item->total_hours, 2),
                    'performance_score' => self::calculatePerformanceScore(
                        $onTimeRate,
                        $item->avg_quality,
                        $item->avg_hours_per_task
                    ),
                ];
            })
            ->sortByDesc('performance_score')
            ->values()
            ->toArray();
    }

    /**
     * Calculate performance score.
     */
    private static function calculatePerformanceScore(float $onTimeRate, ?float $quality, float $efficiency): float
    {
        $qualityScore = $quality ? $quality * 100 : 80;
        $efficiencyScore = $efficiency > 0 ? min(100, (1 / $efficiency) * 10) : 50;
        
        return round(
            ($onTimeRate * 0.4) +
            ($qualityScore * 0.3) +
            ($efficiencyScore * 0.3),
            2
        );
    }
}