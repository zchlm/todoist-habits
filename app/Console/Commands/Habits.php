<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class Habits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'todoist-habits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'To be scheduled everyday at midnight. Increments day for completed Habits & resets those that were not.';

    /** @var Client */
    protected $guzzleClient;

    /** @var string */
    protected $habitRegex = '/\[day (\d+)\]/';

    public function __construct()
    {
        parent::__construct();

        $this->guzzleClient = app(Client::class);
    }

    /**
     * Execute the console command.
     *
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle(): void
    {
        $items = json_decode(
            $this->guzzleClient->request('POST', 'https://todoist.com/api/v7/sync', [
                'json' => [
                    'token' => env('TODOIST_TOKEN'),
                    'sync_token' => '*',
                    'resource_types' => '["items"]',
                ]
            ])->getBody()->getContents(),
            true);

        $habits = array_filter($items['items'], function ($item) {
            return preg_match($this->habitRegex, $item['content']) === 1
                && isset($item['due_date_utc']);
        });

        foreach ($habits as $habit) {
            if (false !== strpos($habit['date_string'], 'workday')
                && Carbon::now()->isWeekend()) {
                continue;
            }

            $dueDate = Carbon::parse($habit['due_date_utc'])->timezone(env('APP_TIMEZONE'));
            if (Carbon::now()->isSameDay($dueDate)) {
                preg_match_all($this->habitRegex, $habit['content'], $matches);
                $streak = (int) $matches[1][0] + 1;
                $this->updateStreak($habit, $streak);
            } elseif ($dueDate->isYesterday()) {
                $this->resetStreak($habit);
            }
        }

        $this->line('All done!');
    }

    /**
     * @param array $habit
     * @param int $streak
     * @param string|null $dateString
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function updateStreak(array $habit, int $streak, string $dateString = null): void
    {
        $dateStringArg = [];

        if ($dateString !== null) {
            $dateStringArg = [
                'date_string' => $dateString
            ];
        }

        $habit['content'] = preg_replace($this->habitRegex, "[day {$streak}]", $habit['content']);

        // update item in Todoist
        $this->guzzleClient->request('POST', 'https://todoist.com/api/v7/sync', [
            'json' => [
                'token' => env('TODOIST_TOKEN'),
                'commands' => [[
                    'type' => 'item_update',
                    'uuid' => uniqid('', true),
                    'args' => array_merge([
                        'id' => $habit['id'],
                        'content' => (string) $habit['content']
                    ], $dateStringArg)
                ]]
            ]
        ]);
    }

    /**
     * @param $habit
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function resetStreak($habit): void
    {
        $dateString = $habit['date_string'] . ' starting today';

        $this->updateStreak($habit, 0, $dateString);
    }
}
