<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use DatabaseMigrations;

    public function test_room_can_be_booked_successfully(): void
    {
        $room = Room::create([
            'name' => 'Meeting Room 1',
        ]);

        $response = $this->postJson(
            "/api/rooms/{$room->id}/book",
            [
                'start_time' => '2026-10-01 10:00:00',
                'end_time' => '2026-10-01 11:00:00',
            ]
        );

        $response
            ->assertStatus(201)
            ->assertJson([
                'message' => 'Room booked successfully.',
            ]);

        $this->assertDatabaseHas('bookings', [
            'room_id' => $room->id,
            'start_time' => '2026-10-01 10:00:00',
            'end_time' => '2026-10-01 11:00:00',
        ]);
    }

    public function test_overlapping_booking_returns_conflict(): void
    {
        $room = Room::create([
            'name' => 'Meeting Room 1',
        ]);

        Booking::create([
            'room_id' => $room->id,
            'start_time' => '2026-10-01 10:00:00',
            'end_time' => '2026-10-01 11:00:00',
        ]);

        $response = $this->postJson(
            "/api/rooms/{$room->id}/book",
            [
                'start_time' => '2026-10-01 10:30:00',
                'end_time' => '2026-10-01 11:30:00',
            ]
        );

        $response
            ->assertStatus(409)
            ->assertJson([
                'message' => 'Room is already booked for this time.',
            ]);

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_booking_requires_valid_dates(): void
    {
        $room = Room::create([
            'name' => 'Meeting Room 1',
        ]);

        $response = $this->postJson(
            "/api/rooms/{$room->id}/book",
            [
                'start_time' => '2026-10-01 11:00:00',
                'end_time' => '2026-10-01 10:00:00',
            ]
        );

        $response->assertStatus(422);

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_room_cannot_be_booked_twice_under_concurrent_requests(): void
    {
        $room = Room::create([
            'name' => 'Concurrent Meeting Room',
        ]);

        $scriptPath = base_path('booking_concurrency_test.php');

        $database = config('database.connections.mysql');

        $script = <<<'PHP'
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$roomId = (int) $argv[1];

$room = \App\Models\Room::findOrFail($roomId);

$controller = new \App\Http\Controllers\BookingController();

$request = \Illuminate\Http\Request::create(
    "/api/rooms/{$roomId}/book",
    'POST',
    [
        'start_time' => '2026-10-02 10:00:00',
        'end_time' => '2026-10-02 11:00:00',
    ]
);

$response = $controller->store($request, $room);

echo $response->getStatusCode();
PHP;

        file_put_contents($scriptPath, $script);

        $processes = [];

        /*
         * Start 10 concurrent booking attempts for the
         * exact same room and exact same time.
         */
        for ($i = 0; $i < 10; $i++) {
            $process = new Process([
                PHP_BINARY,
                $scriptPath,
                (string) $room->id,
            ]);

            $process->setEnv([
                'APP_ENV' => 'testing',

                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => $database['host'],
                'DB_PORT' => $database['port'],
                'DB_DATABASE' => $database['database'],
                'DB_USERNAME' => $database['username'],
                'DB_PASSWORD' => $database['password'] ?? '',

                'CACHE_STORE' => 'array',
                'QUEUE_CONNECTION' => 'sync',
            ]);

            $process->start();

            $processes[] = $process;
        }

        $successful = 0;
        $failed = 0;

        foreach ($processes as $index => $process) {
            $process->wait();

            $output = trim($process->getOutput());
            $errorOutput = trim($process->getErrorOutput());

            if (!$process->isSuccessful()) {
                dump([
                    'process' => $index + 1,
                    'exit_code' => $process->getExitCode(),
                    'output' => $output,
                    'error' => $errorOutput,
                ]);
            }

            if ($output === '201') {
                $successful++;
            } elseif ($output === '409') {
                $failed++;
            } else {
                dump([
                    'process' => $index + 1,
                    'unexpected_output' => $output,
                    'error' => $errorOutput,
                ]);
            }
        }

        /*
         * Only ONE request should successfully create
         * the booking.
         *
         * The other nine requests must detect the
         * existing booking and return 409.
         */
        $this->assertEquals(
            1,
            $successful,
            'Only one concurrent booking should succeed.'
        );

        $this->assertEquals(
            9,
            $failed,
            'The remaining concurrent bookings should return 409.'
        );

        /*
         * Most important assertion:
         *
         * There must only be one booking in the database.
         */
        $this->assertDatabaseCount('bookings', 1);

        $this->assertDatabaseHas('bookings', [
            'room_id' => $room->id,
            'start_time' => '2026-10-02 10:00:00',
            'end_time' => '2026-10-02 11:00:00',
        ]);

        if (file_exists($scriptPath)) {
            unlink($scriptPath);
        }
    }
}