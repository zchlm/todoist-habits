<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class HabitController extends BaseController
{
    /** @var string */
    protected $habitRegex = '/\[day \d+\]/';

    /** @var Client */
    protected $guzzleClient;

    public function __construct()
    {
        $this->guzzleClient = app(Client::class);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle(Request $request)
    {
        if (preg_match($this->habitRegex, $request->input('event_data.content')) !== 1) {
            // not a habit - do nothing
            return response()->json([], 400);
        }

        $data = $request->input('event_data');

        // attempt to find the habit
        $habit = Habit::where('t_id', $data['id'])->first();

        if ($habit !== null) {
            // see if due date has changed
            if ($habit->due_date !== $data['due_date_utc']) {
                // reset habit [day 0]
                $data['content'] = preg_replace($this->habitRegex, '[day 0]', $data['content']);

                // update item in Todoist
                $this->guzzleClient->request('POST', 'https://todoist.com/api/v7/sync', [
                    'json' => [
                        'token' => env('TODOIST_TOKEN'),
                        'commands' => [[
                            'type' => 'item_update',
                            'uuid' => uniqid('', true),
                            'args' => [
                                'id' => $data['id'],
                                'content' => (string) $data['content']
                            ]
                        ]]
                    ]
                ]);
            }

            // update habit
            $habit->update([
                't_id' => $data['id'],
                'content' => $data['content'],
                'date_string' => $data['date_string'],
                'due_date' => $data['due_date_utc'],
            ]);
        } else {
            // habit doesn't exist
            Habit::create([
                't_id' => $data['id'],
                'content' => $data['content'],
                'date_string' => $data['date_string'],
                'due_date' => $data['due_date_utc'],
            ]);
        }

        return response()->json();
    }
}
