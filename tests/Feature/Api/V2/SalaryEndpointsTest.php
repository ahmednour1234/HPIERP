<?php

namespace Tests\Feature\Api\V2;

use App\Models\Salary;
use Database\Seeders\ApiTestingSeeder;
use Tests\Feature\Api\ApiTestCase;

/**
 * A seller's own payslips.
 *
 * The schema stores `month` as YYYY-MM and every money column as a varchar, so
 * the tests lean on the normalisation and the casting as much as the routing.
 */
class SalaryEndpointsTest extends ApiTestCase
{
    private function payslip(array $overrides = []): Salary
    {
        return Salary::create(array_merge([
            'seller_id'          => ApiTestingSeeder::SELLER_ID,
            'month'              => '2026-07',
            'salary'             => '6000',
            'commission'         => '900',
            'transport_amount'   => '750',
            'salary_of_visitors' => '400',
            'other'              => '300',
            'discount'           => '250',
            'total'              => '7200',       // 6000+750+400+300-250
            'number_of_visitors' => '40',
            'result_of_visitors' => '31',
            'number_of_days'     => 26,
            'score'              => '86',
            'note'               => 'لاتوجد ملاحظات',
            'notemanager'        => 'أداء جيد',
        ], $overrides));
    }

    public function test_a_payslip_is_returned_with_every_figure_as_a_number(): void
    {
        $this->payslip();

        $response = $this->asSeller()->getJson('/api/v2/salary?month=2026-07')->assertOk();

        $response->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Salary retrieved')
            ->assertJsonPath('data.month', '2026-07')
            ->assertJsonStructure([
                'data' => [
                    'id', 'month', 'basic', 'transport', 'visits_pay', 'other',
                    'deductions', 'net', 'commission',
                    'visits' => ['target', 'achieved'],
                    'working_days', 'score', 'note', 'manager_note',
                    'status', 'status_text', 'details',
                ],
            ]);

        // The columns are varchars; the point is that clients get numbers
        // rather than strings. JSON renders a whole number as an int, so
        // assert "not a string" rather than a specific numeric type.
        $this->assertIsNumeric($response->json('data.basic'));
        $this->assertIsNumeric($response->json('data.net'));
        $this->assertIsNotString($response->json('data.basic'));
        $this->assertEquals(6000, $response->json('data.basic'));
        $this->assertEquals(250, $response->json('data.deductions'));
    }

    public function test_the_net_is_the_stored_total(): void
    {
        $this->payslip(['total' => '7200']);

        // 6000 + 750 + 400 + 300 - 250, the admin form's own formula.
        $this->assertEquals(
            7200,
            $this->asSeller()->getJson('/api/v2/salary?month=2026-07')->json('data.net')
        );
    }

    public function test_the_net_falls_back_to_the_formula_when_no_total_was_stored(): void
    {
        $this->payslip(['total' => '']);

        $this->assertEquals(
            7200,
            $this->asSeller()->getJson('/api/v2/salary?month=2026-07')->json('data.net')
        );
    }

    public function test_commission_is_reported_but_excluded_from_the_net(): void
    {
        $this->payslip(['commission' => '900', 'total' => '7200']);

        $data = $this->asSeller()->getJson('/api/v2/salary?month=2026-07')->json('data');

        // The admin form records commission but leaves it out of the total.
        $this->assertEquals(900, $data['commission']);
        $this->assertEquals(7200, $data['net']);
    }

    public function test_month_can_be_sent_as_yyyy_mm_or_as_month_and_year(): void
    {
        $this->payslip(['month' => '2026-07']);

        $this->asSeller()->getJson('/api/v2/salary?month=2026-07')
            ->assertOk()->assertJsonPath('data.month', '2026-07');

        // v1 matched the raw input against the column, so `month=7` never
        // matched a row and the list came back empty forever.
        $this->asSeller()->getJson('/api/v2/salary?month=7&year=2026')
            ->assertOk()->assertJsonPath('data.month', '2026-07');
    }

    public function test_a_month_with_no_payslip_answers_200_with_a_null_salary(): void
    {
        // Nothing entered yet is not an error — a 404 would read to a client as
        // "no such endpoint".
        $this->asSeller()->getJson('/api/v2/salary?month=2020-01')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.month', '2020-01')
            ->assertJsonPath('data.salary', null);
    }

    public function test_the_breakdown_lists_only_the_lines_that_apply(): void
    {
        $this->payslip(['other' => '0', 'discount' => '0']);

        $details = $this->asSeller()->getJson('/api/v2/salary?month=2026-07')->json('data.details');

        $labels = array_column($details, 'label');
        $this->assertContains('الراتب الأساسي', $labels);
        $this->assertContains('بدل انتقال', $labels);
        $this->assertNotContains('أخرى', $labels);
        $this->assertNotContains('خصومات', $labels);
    }

    public function test_a_deduction_is_marked_as_such(): void
    {
        $this->payslip(['discount' => '250']);

        $details = $this->asSeller()->getJson('/api/v2/salary?month=2026-07')->json('data.details');

        $deduction = collect($details)->firstWhere('type', 'deduct');
        $this->assertNotNull($deduction);
        $this->assertEquals(250, $deduction['amount']);
    }

    public function test_history_returns_payslips_newest_first(): void
    {
        $this->payslip(['month' => '2026-05', 'total' => '7000']);
        $this->payslip(['month' => '2026-06', 'total' => '7100']);
        $this->payslip(['month' => '2026-07', 'total' => '7200']);

        $response = $this->asSeller()->getJson('/api/v2/salary/history')->assertOk();

        $this->assertSame(
            ['2026-07', '2026-06', '2026-05'],
            array_column($response->json('data'), 'month')
        );
    }

    public function test_history_bounds_its_limit(): void
    {
        $this->asSeller()->getJson('/api/v2/salary/history?limit=99')
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['limit']]);
    }

    public function test_a_seller_only_sees_their_own_payslip(): void
    {
        $this->payslip(['total' => '7200']);
        $this->payslip(['seller_id' => 999999, 'total' => '99999']);

        $this->assertEquals(
            7200,
            $this->asSeller()->getJson('/api/v2/salary?month=2026-07')->json('data.net')
        );

        $this->assertCount(1, $this->asSeller()->getJson('/api/v2/salary/history')->json('data'));
    }

    public function test_salary_requires_authentication(): void
    {
        $this->getJson('/api/v2/salary')->assertStatus(401);
        $this->getJson('/api/v2/salary/history')->assertStatus(401);
    }
}
