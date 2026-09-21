<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * خريطة المناديب لا تعرض إلا إحداثيًّا صالحًا.
 *
 * كان الفحص بـCAST داخل SQL، وسلوكه يختلف بين MariaDB وSQLite، فمرّ
 * صفّ إلى الإنتاج ظهرت علامته في خليج غينيا — أي (0,0).
 */
class SellerMapTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string, string, bool}> */
    public function coordinates(): array
    {
        return [
            'valid Egyptian point' => ['26.7', '31.6', true],
            'both zero'            => ['0', '0', false],
            'zero latitude'        => ['0', '30.8', false],
            'zero longitude'       => ['26.7', '0', false],
            'decimal zero'         => ['0.00000', '0.00000', false],
            'not a number'         => ['abc', 'xyz', false],
            'latitude out of range'=> ['999', '31.6', false],
            'longitude out of range'=> ['26.7', '999', false],
        ];
    }

    /** @dataProvider coordinates */
    public function test_only_valid_coordinates_reach_the_map(string $lat, string $lng, bool $kept): void
    {
        $id = $this->admin($lat, $lng);

        $admins = app(AdminController::class)->showmap()->getData()['admins'];

        $this->assertSame(
            $kept,
            $admins->contains(fn ($a) => (int) $a->id === $id),
            "lat={$lat} lng={$lng}"
        );
    }

    private function admin(string $lat, string $lng): int
    {
        $id = (int) (DB::table('admins')->max('id') ?? 0) + 1;

        DB::table('admins')->insert([
            'id' => $id, 'local_id' => 0,
            'f_name' => 'Map', 'l_name' => 'Probe', 'name_en' => 'Map Probe',
            'email' => "map{$id}@test.test", 'password' => bcrypt('secret'),
            'role' => 'admin', 'is_super' => 0,
            'latitude' => $lat, 'longitude' => $lng,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }
}
