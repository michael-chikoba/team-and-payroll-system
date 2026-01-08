<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Employee;

class PayrollCalculationTest extends TestCase
{
    /**
     * Generic payroll assertion helper
     */
    private function assertPayroll(Employee $employee, string $expectedCountryCode)
    {
        $this->assertNotNull($employee, 'Employee not found');
        $this->assertEquals(
            $expectedCountryCode,
            $employee->getCountryCode(),
            'Employee country mismatch'
        );

        $taxConfig = $employee->getTaxConfiguration();

        $this->assertNotNull($taxConfig, 'Tax configuration not found');
        $this->assertEquals(
            $expectedCountryCode,
            $taxConfig->country_code,
            'Tax config country mismatch'
        );

        $result = $taxConfig->calculatePayroll($employee, 0, 0);

        // Structure checks
        $this->assertArrayHasKey('basic_salary', $result);
        $this->assertArrayHasKey('gross_salary', $result);
        $this->assertArrayHasKey('net_salary', $result);
        $this->assertArrayHasKey('deductions', $result);

        // Value sanity checks
        $this->assertGreaterThan(0, $result['basic_salary']);
        $this->assertGreaterThanOrEqual(
            0,
            $result['net_salary'],
            'Net salary cannot be negative'
        );

        // Must have statutory deductions configured
        $this->assertIsArray($result['deductions']['statutory']);

        dump([
            'Employee' => $employee->user->first_name . ' ' . $employee->user->last_name,
            'Country' => $employee->getCountryCode(),
            'Tax Config' => $taxConfig->country_name,
            'Basic Salary' => $result['basic_salary'],
            'Gross Salary' => $result['gross_salary'],
            'Net Salary' => $result['net_salary'],
            'Statutory Deductions' => $result['deductions']['statutory'],
        ]);
    }

    /* ===============================
     |  NAMIBIA (NA) PAYROLL TESTS
     |===============================*/

    public function test_namibia_employee_1_payroll()
    {
        $employee = Employee::with(['country', 'business', 'user'])->find(56);
        $this->assertPayroll($employee, 'NA');
    }

    /* ===============================
     |  ZAMBIA (ZM) PAYROLL TESTS
     |===============================*/

    public function test_zambia_employee_1_payroll()
    {
        $employee = Employee::with(['country', 'business', 'user'])->find(4);
        $this->assertPayroll($employee, 'ZM');
    }

    public function test_zambia_employee_2_payroll()
    {
        $employee = Employee::with(['country', 'business', 'user'])->find(8);
        $this->assertPayroll($employee, 'ZM');
    }

    public function test_zambia_employee_3_payroll()
    {
        $employee = Employee::with(['country', 'business', 'user'])->find(24);
        $this->assertPayroll($employee, 'ZM');
    }
}
