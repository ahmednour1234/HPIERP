<?php

namespace Tests\Feature\Api\V1;

use Tests\Feature\Api\ApiTestCase;

/**
 * تسجيل الحضور يلزمه موقع.
 *
 * كانت lat/lon اختياريتين، فتُقبل البصمة بلا إحداثيات ويخرج عمود
 * الموقع في اللوحة فارغًا دون أن يخالف أحدٌ شيئًا.
 */
class AttendanceLocationRequiredTest extends ApiTestCase
{
    private function punch(array $extra = [])
    {
        return $this->asSeller()->postJson('/api/v1/attendance/store', array_merge([
            'status' => 1,
        ], $extra));
    }

    /** @test */
    public function a_punch_without_coordinates_is_refused(): void
    {
        $this->punch()->assertStatus(422)->assertJsonValidationErrors(['lat', 'lon']);
    }

    /** @test */
    public function a_punch_with_only_latitude_is_refused(): void
    {
        $this->punch(['lat' => 30.1])->assertStatus(422)->assertJsonValidationErrors(['lon']);
    }

    /**
     * (0,0) ليست بصمة: جهازٌ لم يحدّد موقعه يرسلها أحيانًا.
     *
     * @test
     */
    public function the_null_island_coordinates_are_refused(): void
    {
        $this->punch(['lat' => 0, 'lon' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['lat', 'lon']);
    }

    /** @test */
    public function coordinates_out_of_range_are_refused(): void
    {
        $this->punch(['lat' => 120, 'lon' => 30.1])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['lat']);
    }

    /** وبموقع صحيح تمرّ البصمة. */
    public function test_a_punch_with_a_location_is_accepted(): void
    {
        $response = $this->punch(['lat' => 30.0444, 'lon' => 31.2357]);

        $this->assertNotSame(
            422,
            $response->status(),
            'بصمة بموقع صحيح يجب ألّا تُرفض بالتحقق.'
        );
    }
}
