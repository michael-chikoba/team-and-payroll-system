<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlaLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'ticket_id',
        'sla_type',
        'target_hours',
        'actual_hours',
        'is_met',
        'breach_reason',
        'severity_level',
        'automated_check',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_met' => 'boolean',
        'target_hours' => 'integer',
        'actual_hours' => 'integer',
        'automated_check' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the ticket associated with this SLA log.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Scope a query to only include met SLAs.
     */
    public function scopeMet($query)
    {
        return $query->where('is_met', true);
    }

    /**
     * Scope a query to only include breached SLAs.
     */
    public function scopeBreached($query)
    {
        return $query->where('is_met', false);
    }

    /**
     * Scope a query to only include specific SLA types.
     */
    public function scopeType($query, $type)
    {
        return $query->where('sla_type', $type);
    }

    /**
     * Scope a query to only include SLAs within a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Calculate the breach percentage.
     */
    public function getBreachPercentageAttribute(): float
    {
        if ($this->target_hours == 0) {
            return 0;
        }
        
        if ($this->is_met) {
            return 0;
        }
        
        $breach = $this->actual_hours - $this->target_hours;
        return ($breach / $this->target_hours) * 100;
    }

    /**
     * Get the breach duration in hours.
     */
    public function getBreachDurationAttribute(): int
    {
        if ($this->is_met) {
            return 0;
        }
        
        return max(0, $this->actual_hours - $this->target_hours);
    }

    /**
     * Determine if this is a major breach.
     */
    public function getIsMajorBreachAttribute(): bool
    {
        if ($this->is_met) {
            return false;
        }
        
        $breachDuration = $this->breach_duration;
        
        if ($this->sla_type === 'response_time') {
            return $breachDuration > 24; // More than 1 day late
        }
        
        if ($this->sla_type === 'resolution_time') {
            return $breachDuration > 72; // More than 3 days late
        }
        
        return $breachDuration > 48; // Default threshold
    }

    /**
     * Get the SLA type label.
     */
    public function getSlaTypeLabelAttribute(): string
    {
        $labels = [
            'response_time' => 'Response Time',
            'resolution_time' => 'Resolution Time',
            'completion_time' => 'Completion Time',
            'acknowledgment_time' => 'Acknowledgment Time',
            'update_time' => 'Update Time',
        ];
        
        return $labels[$this->sla_type] ?? ucfirst(str_replace('_', ' ', $this->sla_type));
    }

    /**
     * Get the severity level label.
     */
    public function getSeverityLabelAttribute(): string
    {
        $levels = [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'critical' => 'Critical',
        ];
        
        return $levels[$this->severity_level] ?? 'Not Set';
    }

    /**
     * Get color based on SLA status.
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->is_met) {
            return 'success';
        }
        
        if ($this->is_major_breach) {
            return 'danger';
        }
        
        return 'warning';
    }

    /**
     * Get icon based on SLA status.
     */
    public function getStatusIconAttribute(): string
    {
        if ($this->is_met) {
            return '✅';
        }
        
        if ($this->is_major_breach) {
            return '❌';
        }
        
        return '⚠️';
    }

    /**
     * Log a new SLA check.
     */
    public static function logCheck(
        Ticket $ticket,
        string $slaType,
        int $targetHours,
        int $actualHours,
        ?string $breachReason = null
    ): self {
        return self::create([
            'ticket_id' => $ticket->id,
            'sla_type' => $slaType,
            'target_hours' => $targetHours,
            'actual_hours' => $actualHours,
            'is_met' => $actualHours <= $targetHours,
            'breach_reason' => $breachReason,
            'severity_level' => $actualHours <= $targetHours ? null : $this->determineSeverity($slaType, $actualHours, $targetHours),
            'automated_check' => true,
        ]);
    }

    /**
     * Determine severity level based on breach.
     */
    private static function determineSeverity(string $slaType, int $actualHours, int $targetHours): string
    {
        $breachPercentage = (($actualHours - $targetHours) / $targetHours) * 100;
        
        if ($breachPercentage > 100) {
            return 'critical';
        }
        
        if ($breachPercentage > 50) {
            return 'high';
        }
        
        if ($breachPercentage > 25) {
            return 'medium';
        }
        
        return 'low';
    }

    /**
     * Get statistics for a specific period.
     */
    public static function getStatistics(string $period = 'last_30_days', ?int $userId = null): array
    {
        $query = self::query();
        
        if ($userId) {
            $query->whereHas('ticket', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereHas('assignedUsers', function ($q2) use ($userId) {
                      $q2->where('user_id', $userId);
                  });
            });
        }
        
        $query->betweenDates(now()->subDays(30), now());
        
        $total = $query->count();
        $met = $query->clone()->met()->count();
        $breached = $query->clone()->breached()->count();
        
        return [
            'total' => $total,
            'met' => $met,
            'breached' => $breached,
            'compliance_rate' => $total > 0 ? round(($met / $total) * 100, 2) : 0,
            'breach_rate' => $total > 0 ? round(($breached / $total) * 100, 2) : 0,
            'by_type' => $query->clone()
                ->selectRaw('sla_type, COUNT(*) as count, SUM(CASE WHEN is_met = 1 THEN 1 ELSE 0 END) as met_count')
                ->groupBy('sla_type')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->sla_type => [
                        'total' => $item->count,
                        'met' => $item->met_count,
                        'compliance_rate' => $item->count > 0 ? round(($item->met_count / $item->count) * 100, 2) : 0,
                    ]];
                })
                ->toArray(),
        ];
    }
}