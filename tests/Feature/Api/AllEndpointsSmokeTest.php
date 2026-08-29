<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Route;

/**
 * Exercises every registered API endpoint and records what it returned.
 *
 * This is a smoke test, not a correctness test: it asserts that no endpoint
 * 500s or dies with an unhandled error, and writes a full report of status
 * codes, headers and response bodies to storage/logs/api-smoke-report.md.
 *
 * GET endpoints are called with no parameters, so many legitimately answer
 * 4xx (missing id, missing filter). Those are recorded, not failed — only
 * server errors and unhandled exceptions fail the run.
 */
class AllEndpointsSmokeTest extends ApiTestCase
{
    /**
     * No endpoint may return a server error. This started at 17 and was
     * ratcheted down as the underlying bugs were fixed; at zero it is simply
     * the rule, so any new 5xx fails the build.
     */
    private const KNOWN_SERVER_ERRORS = 0;

    /** Endpoints skipped, with the reason. */
    private const SKIP = [
        // Mutates or wipes the seller's working tables; covered by its own test.
        'api/v1/stocks/confirm'         => 'destructive settlement',
        'api/v2/stocks/confirm'         => 'destructive settlement',
        // Streams a file download rather than JSON.
        'api/v1/product/export'         => 'file download',
        'api/v1/transaction/transfer/export' => 'file download',
        'api/v1/product/download/excel/sample' => 'file download',
        'api/v1/product/barcode/generate' => 'image stream',
    ];

    public function test_every_endpoint_responds_without_a_server_error(): void
    {
        $rows = [];
        $failures = [];

        foreach ($this->apiRoutes() as $route) {
            $uri    = $route['uri'];
            $method = $route['method'];

            if (isset(self::SKIP[$uri])) {
                $rows[] = ['uri' => $uri, 'method' => $method, 'status' => '-',
                           'note' => 'skipped: ' . self::SKIP[$uri], 'body' => '', 'headers' => []];
                continue;
            }

            // Substitute a real id for path parameters.
            $path = $this->fillParameters($uri);

            $this->asSeller();
            $response = $this->withHeaders($this->jsonHeaders())->json($method, '/' . $path);

            $status = $response->getStatusCode();
            $body   = $response->getContent();

            $rows[] = [
                'uri'     => $uri,
                'method'  => $method,
                'status'  => $status,
                'note'    => '',
                'body'    => $body,
                'headers' => $this->interestingHeaders($response->headers->all()),
            ];

            if ($status >= 500) {
                $failures[] = sprintf('%s %s -> %d: %s', $method, $path, $status,
                    substr(strip_tags($body), 0, 300));
            }
        }

        $this->writeReport($rows, $failures);

        // Every endpoint must answer without a server error.
        $this->assertLessThanOrEqual(
            self::KNOWN_SERVER_ERRORS,
            count($failures),
            sprintf(
                "Server errors rose above the known baseline of %d.\nSee storage/logs/api-smoke-report.md\n\n%s",
                self::KNOWN_SERVER_ERRORS,
                implode("\n", $failures)
            )
        );
    }

    /** Every GET/POST/PUT/DELETE route under api/, deduplicated. */
    private function apiRoutes(): array
    {
        $out = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (strpos($uri, 'api/') !== 0) continue;
            if (strpos($uri, 'fallbackPlaceholder') !== false) continue;

            $methods = array_values(array_diff($route->methods(), ['HEAD', 'OPTIONS']));
            if (!$methods) continue;

            $out[$uri . '|' . $methods[0]] = ['uri' => $uri, 'method' => $methods[0]];
        }

        ksort($out);

        return array_values($out);
    }

    /** Replace {param} with an id the fixtures actually contain. */
    private function fillParameters(string $uri): string
    {
        return preg_replace_callback('/\{([a-zA-Z_]+)\??\}/', function ($m) {
            switch ($m[1]) {
                case 'type': return '1';
                case 'id':   return '90001';   // fixture customer / product id
                default:     return '1';
            }
        }, $uri);
    }

    private function interestingHeaders(array $headers): array
    {
        $keep = ['content-type', 'cache-control', 'x-ratelimit-limit', 'x-ratelimit-remaining'];
        $out = [];
        foreach ($keep as $k) {
            if (isset($headers[$k])) $out[$k] = implode(', ', (array) $headers[$k]);
        }
        return $out;
    }

    /** A readable report of what every endpoint returned. */
    private function writeReport(array $rows, array $failures): void
    {
        $path = storage_path('logs/api-smoke-report.md');
        @mkdir(dirname($path), 0775, true);

        $byStatus = [];
        foreach ($rows as $r) {
            $key = (string) $r['status'];
            $byStatus[$key] = ($byStatus[$key] ?? 0) + 1;
        }
        ksort($byStatus);

        $out  = "# API smoke report\n\n";
        $out .= 'Endpoints exercised: **' . count($rows) . "**  \n";
        $out .= 'Server errors (5xx): **' . count($failures) . "**\n\n";

        $out .= "## Status distribution\n\n| Status | Count |\n|---|---|\n";
        foreach ($byStatus as $status => $count) {
            $out .= "| $status | $count |\n";
        }

        $out .= "\n## Per-endpoint detail\n\n";
        foreach ($rows as $r) {
            $out .= '### `' . $r['method'] . ' /' . $r['uri'] . "`\n\n";
            if ($r['note']) {
                $out .= '_' . $r['note'] . "_\n\n";
                continue;
            }
            $out .= '**Status:** ' . $r['status'] . "\n\n";
            if ($r['headers']) {
                $out .= "**Headers**\n\n```\n";
                foreach ($r['headers'] as $k => $v) $out .= "$k: $v\n";
                $out .= "```\n\n";
            }
            $body = $r['body'];
            $pretty = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $body = json_encode($pretty, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            if (strlen($body) > 1500) $body = substr($body, 0, 1500) . "\n... (truncated)";
            $out .= "**Body**\n\n```json\n" . $body . "\n```\n\n";
        }

        file_put_contents($path, $out);
    }
}
