<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasEncryptedFields;

class Payslip extends Model
{
    use HasFactory;
    use HasEncryptedFields;

    protected $table = 'payslips';

    protected $fillable = [
        'employee_id',
        'payroll_id',
        'pay_period_start',
        'pay_period_end',
        'payment_date',
        'basic_salary',
        'house_allowance',
        'transport_allowance',
        'other_allowances',
        'overtime_hours',
        'overtime_rate',
        'overtime_pay',
        'bonuses',
        'gross_salary',
        'gross_pay',
        'tax_deductions',
        'napsa',
        'paye',
        'nhima',
        'pension',
        'other_deductions',
        'total_deductions',
        'net_pay',
        'status',
        'breakdown',
        'pdf_path',
        'is_sent',
    ];

    protected $casts = [
        'pay_period_start' => 'date',
        'pay_period_end'   => 'date',
        'payment_date'     => 'date',
        'breakdown'        => 'array',
        'is_sent'          => 'boolean',
        // Financial fields are encrypted via the trait — NOT cast as numeric here.
        // Casting to float after the trait decrypts works fine via the accessor;
        // adding a cast here would cause a double-decode on the ciphertext.
    ];

    protected $appends = [
        'total_earnings',
        'deductions_array',
        'statutory_deductions',
        'allowances_array',
        'total_statutory_deductions',
        'napsa_amount',
        'nhima_amount',
        'pension_amount',
        'currency',
    ];

    // =========================================================================
    // ENCRYPTED FIELDS
    // =========================================================================

    /**
     * Fields stored as ciphertext in the database.
     *
     * Notes:
     *  - status IS encrypted (reveals pay-cycle state).
     *  - breakdown is NOT listed — it is cast to array via $casts and the trait
     *    skips array values automatically.
     *  - pdf_path is NOT encrypted — it is an internal server path that is
     *    never returned to clients (enforced in controllers / formatters).
     *    It must remain plaintext so Storage::exists() and Storage::download()
     *    can use it without going through the decrypt layer.
     */
    public function getEncryptedFields(): array
    {
        return [
            'basic_salary',
            'house_allowance',
            'transport_allowance',
            'other_allowances',
            'overtime_rate',
            'overtime_pay',
            'gross_salary',
            'gross_pay',
            'tax_deductions',
            'napsa',
            'paye',
            'nhima',
            'pension',
            'other_deductions',
            'total_deductions',
            'net_pay',
            'status',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('pay_period_start', [$startDate, $endDate]);
    }

    /**
     * status is encrypted — WHERE status = ? will never match ciphertext.
     * Filter in PHP after fetching.
     */
    public function scopeByStatus($query, $status)
    {
        return $query; // filtering done in PHP by callers: ->get()->filter(fn($p) => $p->status === $status)
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->whereHas('employee', fn($q) => $q->where('department', $department));
    }

    public function scopeByBusiness($query, $businessId)
    {
        return $query->whereHas('employee', fn($q) => $q->where('business_id', $businessId));
    }

    public function scopeByCountry($query, $countryId)
    {
        return $query->whereHas('employee', fn($q) => $q->where('country_id', $countryId));
    }

    public function scopeSent($query)
    {
        return $query->where('is_sent', true);
    }

    public function scopeNotSent($query)
    {
        return $query->where('is_sent', false);
    }

    // =========================================================================
    // ACCESSORS & COMPUTED PROPERTIES
    // =========================================================================

    public function getTotalEarningsAttribute(): float
    {
        return (float) ($this->gross_salary ?? 0);
    }

    public function getDeductionsArrayAttribute(): array
    {
        return [
            'paye'             => (float) ($this->paye             ?? 0),
            'napsa'            => (float) ($this->napsa            ?? 0),
            'nhima'            => (float) ($this->nhima            ?? 0),
            'pension'          => (float) ($this->pension          ?? 0),
            'other_deductions' => (float) ($this->other_deductions ?? 0),
            'total'            => (float) ($this->total_deductions ?? 0),
        ];
    }

    public function getEmployerContributionsAttribute(): array
    {
        $breakdown    = $this->breakdown ?? [];
        $employerTotal = $breakdown['deductions_breakdown']['employer_total'] ?? 0;
        return ['total' => round((float) $employerTotal, 2)];
    }

    public function getStatutoryDeductionsAttribute(): array
    {
        $breakdown = $this->breakdown ?? [];
        return $breakdown['deductions_breakdown']['statutory_breakdown'] ?? [];
    }

    public function getAllowancesArrayAttribute(): array
    {
        $breakdown  = $this->breakdown ?? [];
        $allowances = $breakdown['earnings_breakdown']['allowances'] ?? [];

        return [
            'housing'   => (float) ($allowances['housing']   ?? $this->house_allowance      ?? 0),
            'transport' => (float) ($allowances['transport']  ?? $this->transport_allowance  ?? 0),
            'lunch'     => (float) ($allowances['lunch']      ?? $this->other_allowances     ?? 0),
            'total'     => (float) ($allowances['total']      ??
                (($this->house_allowance ?? 0) + ($this->transport_allowance ?? 0) + ($this->other_allowances ?? 0))),
        ];
    }

    public function getTotalStatutoryDeductionsAttribute(): float
    {
        return (float) collect($this->statutory_deductions)->sum('amount');
    }

    public function getNapsaAmountAttribute(): float
    {
        $napsa = $this->getDeductionByName('NAPSA');
        if ($napsa && ($napsa['type'] ?? '') === 'levy') {
            return (float) ($napsa['amount'] ?? 0);
        }
        return (float) ($this->napsa ?? 0);
    }

    public function getNhimaAmountAttribute(): float
    {
        $nhima = $this->getDeductionByType('health');
        return $nhima ? (float) ($nhima['amount'] ?? 0) : (float) ($this->nhima ?? 0);
    }

    public function getPensionAmountAttribute(): float
    {
        $pensionTotal = 0;
        foreach ($this->statutory_deductions as $deduction) {
            if (($deduction['type'] ?? '') === 'pension' &&
                stripos($deduction['name'] ?? '', 'NAPSA') === false) {
                $pensionTotal += (float) ($deduction['amount'] ?? 0);
            }
        }
        return $pensionTotal ?: (float) ($this->pension ?? 0);
    }

    public function getCurrencyAttribute(): string
    {
        return ($this->breakdown ?? [])['currency'] ?? 'ZMW';
    }

    public function getTaxConfigAttribute(): ?array
    {
        $breakdown = $this->breakdown ?? [];
        if (!isset($breakdown['tax_config_id'])) return null;

        return [
            'id'          => $breakdown['tax_config_id'],
            'type'        => $breakdown['tax_config_type']        ?? 'unknown',
            'country'     => $breakdown['tax_config_country']     ?? null,
            'business_id' => $breakdown['tax_config_business_id'] ?? null,
            'currency'    => $breakdown['currency']               ?? 'USD',
        ];
    }

    public function getUsesDynamicTaxConfigAttribute(): bool
    {
        return isset(($this->breakdown ?? [])['tax_config_id']);
    }

    public function getEarningsBreakdownAttribute(): array
    {
        $breakdown = $this->breakdown ?? [];
        $earnings  = $breakdown['earnings_breakdown'] ?? [];

        return [
            'basic_salary' => (float) ($earnings['basic_salary'] ?? $this->basic_salary  ?? 0),
            'allowances'   => $earnings['allowances']            ?? $this->allowances_array,
            'overtime'     => $earnings['overtime']              ?? [
                'pay'   => (float) ($this->overtime_pay   ?? 0),
                'hours' => (float) ($this->overtime_hours ?? 0),
            ],
            'bonuses'     => (float) ($earnings['bonuses']     ?? $this->bonuses      ?? 0),
            'gross_total' => (float) ($earnings['gross_total'] ?? $this->gross_salary ?? 0),
        ];
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    public function getAllDeductionsFlat(): array
    {
        $deductions = [];

        if (($this->paye ?? 0) > 0) {
            $deductions['PAYE Tax'] = ['amount' => (float) $this->paye, 'type' => 'tax', 'name' => 'PAYE Tax'];
        }

        foreach ($this->statutory_deductions as $deduction) {
            $name              = $deduction['name'] ?? 'Unknown';
            $deductions[$name] = ['amount' => (float) ($deduction['amount'] ?? 0), 'type' => $deduction['type'] ?? 'statutory', 'name' => $name];
        }

        if (($this->napsa ?? 0) > 0 && !isset($deductions['NAPSA'])) {
            $deductions['NAPSA'] = ['amount' => (float) $this->napsa, 'type' => 'levy', 'name' => 'NAPSA'];
        }
        if (($this->nhima ?? 0) > 0 && !isset($deductions['NHIMA'])) {
            $deductions['NHIMA'] = ['amount' => (float) $this->nhima, 'type' => 'health', 'name' => 'NHIMA'];
        }
        if (($this->pension ?? 0) > 0 && !isset($deductions['Pension'])) {
            $deductions['Pension'] = ['amount' => (float) $this->pension, 'type' => 'pension', 'name' => 'Pension'];
        }
        if (($this->other_deductions ?? 0) > 0) {
            $deductions['Other Deductions'] = ['amount' => (float) $this->other_deductions, 'type' => 'other', 'name' => 'Other Deductions'];
        }

        return $deductions;
    }

    public function getDeductionByType(string $type): ?array
    {
        foreach ($this->statutory_deductions as $deduction) {
            if (($deduction['type'] ?? '') === $type) return $deduction;
        }
        return null;
    }

    public function getDeductionAmount(string $deductionName): float
    {
        return (float) ($this->getAllDeductionsFlat()[$deductionName]['amount'] ?? 0.0);
    }

    public function getDeductionByName(string $name): ?array
    {
        foreach ($this->statutory_deductions as $deduction) {
            if (stripos($deduction['name'] ?? '', $name) !== false) return $deduction;
        }
        return null;
    }

    /**
     * Read the raw (possibly ciphertext) status directly from attributes.
     * Used internally by PayslipGeneratorService to avoid triggering the
     * trait's getAttribute() when only the raw value is needed.
     */
    public function getRawStatus(): ?string
    {
        return $this->attributes['status'] ?? null;
    }

    // =========================================================================
    // FORMATTING — API responses
    // =========================================================================

    /**
     * Detailed API response.
     *
     * pdf_path is intentionally EXCLUDED — clients must use the /download
     * endpoint. Only a boolean pdf_available flag is returned.
     */
    public function toDetailedArray(): array
    {
        return [
            'id'       => $this->id,
            'employee' => [
                'id'          => $this->employee->id          ?? null,
                'employee_id' => $this->employee->employee_id ?? null,
                'name'        => $this->employee->full_name   ?? 'N/A',
                'department'  => $this->employee->department  ?? 'Unassigned',
            ],
            'period' => [
                'start'        => $this->pay_period_start?->format('Y-m-d'),
                'end'          => $this->pay_period_end?->format('Y-m-d'),
                'payment_date' => $this->payment_date?->format('Y-m-d'),
            ],

            // All financial values — trait decrypts via getAttribute()
            'basic_salary'        => (float) ($this->basic_salary        ?? 0),
            'house_allowance'     => (float) ($this->house_allowance     ?? 0),
            'transport_allowance' => (float) ($this->transport_allowance ?? 0),
            'other_allowances'    => (float) ($this->other_allowances    ?? 0),
            'overtime'            => [
                'hours' => (float) ($this->overtime_hours ?? 0),
                'rate'  => (float) ($this->overtime_rate  ?? 0),
                'pay'   => (float) ($this->overtime_pay   ?? 0),
            ],
            'bonuses'         => (float) ($this->bonuses         ?? 0),
            'gross_salary'    => (float) ($this->gross_salary    ?? 0),
            'gross_pay'       => (float) ($this->gross_pay       ?? 0),
            'tax_deductions'  => (float) ($this->tax_deductions  ?? 0),
            'total_deductions'=> (float) ($this->total_deductions ?? 0),
            'deductions' => [
                'napsa'   => (float) ($this->napsa            ?? 0),
                'paye'    => (float) ($this->paye             ?? 0),
                'nhima'   => (float) ($this->nhima            ?? 0),
                'pension' => (float) ($this->pension          ?? 0),
                'other'   => (float) ($this->other_deductions ?? 0),
            ],
            'net_pay'       => (float) ($this->net_pay ?? 0),
            'currency'      => $this->currency,
            'status'        => $this->status ?? 'pending',
            'is_sent'       => (bool) ($this->is_sent ?? false),
            'breakdown'     => $this->breakdown,
            'pdf_available' => !empty($this->attributes['pdf_path']),  // boolean only — path never exposed
            'created_at'    => $this->created_at?->toISOString(),
            'updated_at'    => $this->updated_at?->toISOString(),
        ];
    }

    public function toReportArray(): array
    {
        $employee   = $this->employee;
        $deductions = $this->getAllDeductionsFlat();

        return [
            'id'            => $this->id,
            'employee_id'   => $this->employee_id,
            'employee_name' => $employee && $employee->user
                ? trim(($employee->user->first_name ?? '') . ' ' . ($employee->user->last_name ?? ''))
                : 'N/A',
            'business'      => $employee && $employee->business ? $employee->business->name : 'No Business',
            'business_id'   => $employee->business_id ?? null,
            'country'       => $employee && $employee->country ? $employee->country->name : 'N/A',
            'country_code'  => $employee && $employee->country ? $employee->country->code : null,
            'department'    => $employee->department ?? 'Unassigned',
            'pay_period'    => $this->pay_period_start && $this->pay_period_end
                ? $this->pay_period_start->format('M d, Y') . ' – ' . $this->pay_period_end->format('M d, Y')
                : 'N/A',
            'gross_salary'    => (float) ($this->gross_salary    ?? 0),
            'net_salary'      => (float) ($this->net_pay         ?? 0),
            'total_deductions'=> (float) ($this->total_deductions ?? 0),
            'deductions'      => $deductions,
            'currency'        => $this->currency,
            'status'          => $this->status ?? 'pending',
            'is_sent'         => (bool) ($this->is_sent ?? false),
            'created_at'      => $this->created_at?->toDateTimeString(),
        ];
    }

    // =========================================================================
    // BUSINESS LOGIC
    // =========================================================================

    public function markAsSent(): bool
    {
        $this->is_sent = true;
        return $this->save();
    }

    public function generatePdf(): ?string
    {
        return null;
    }
}