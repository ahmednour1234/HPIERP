<?php
/**
 * Ask each v2 FormRequest for its real rules().
 *
 * The rules are built at runtime (a $required variable switches between
 * "required" and "sometimes" depending on whether an id is present), so
 * reading the source statically misses them. Booting the class and calling
 * rules() gives exactly what the endpoint enforces.
 *
 * Usage: php artisan tinker --execute="require 'tools/apidoc/v2_rules.php';"
 *   or:  php tools/apidoc/v2_rules.php   (bootstraps Laravel itself)
 */

if (!isset($app)) {
    require __DIR__ . '/../../vendor/autoload.php';
    $app = require __DIR__ . '/../../bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
}

$out = [];

foreach (glob(__DIR__ . '/../../app/Http/Requests/Api/V1/*.php') as $file) {
    $class = 'App\\Http\\Requests\\Api\\V1\\' . basename($file, '.php');
    if (!class_exists($class)) continue;

    foreach ([false, true] as $withId) {
        /** @var Illuminate\Foundation\Http\FormRequest $request */
        $request = $class::create('/', 'POST', $withId ? ['id' => 1] : []);
        $request->setContainer(app());

        try {
            $rules = $request->rules();
        } catch (Throwable $e) {
            continue;
        }

        $normalised = [];
        foreach ($rules as $field => $rule) {
            if (is_array($rule)) {
                $rule = implode('|', array_map(
                    fn($r) => is_object($r) ? (method_exists($r, '__toString') ? (string) $r : get_class($r)) : $r,
                    $rule
                ));
            }
            $normalised[$field] = $rule;
        }

        $out[basename($file, '.php')][$withId ? 'update' : 'create'] = $normalised;
    }
}

file_put_contents(__DIR__ . '/../../storage/app/v2-rules.json',
    json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo count($out) . " request classes\n";
