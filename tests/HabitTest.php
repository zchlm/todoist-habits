<?php

use Mockery as m;
use GuzzleHttp\Client;
use Laravel\Lumen\Testing\DatabaseMigrations;

class HabitTest extends TestCase
{
    use DatabaseMigrations;

    /** @var array */
    protected $body;

    /** @var m\Mock */
    protected $mc;

    public function setUp()
    {
        parent::setUp();

        $this->body = json_decode('{"event_name":"item:updated", "initiator":{"is_premium":true,"image_id":"9cbe0180018d6093ed832aecbdb53360","id":16134658,"full_name":"Zach","email":"zach@zachmoore.xyz"}," version":"7","user_id":16134658,"event_data":{"assigned_by_uid":null,"is_archived":0,"labels":[2149502548],"sync_id":null, "date_completed":null,"all_day":false,"in_history":0,"indent":1,"date_added":"Sun 12 Aug 2018 00:59:43 +0000","checked":0,"date_lang":"en", "id":9999999999,"content":"!!test!! habit [day 3]","is_deleted":0,"user_id":16134658, "url":"https:\/\/todoist.com\/showTask?id=2766735392","due_date_utc":"Fri 28 Sep 2018 10:30:00 +0000","priority":4, "parent_id":null,"item_order":2,"responsible_uid":null,"project_id":2194974002,"collapsed":0, "date_string":"ev workday 8:30pm"}}', true);

        $this->mc = m::mock(Client::class);
        $this->app->instance(Client::class, $this->mc);
    }

    /**
     * @test
     * @return void
     */
    public function todoist_webhooks_user_agent_must_be_set(): void
    {
        $this->post('/', $this->body)->seeStatusCode(406);

        $this->post('/', $this->body, [
            'User-Agent' => 'Todoist-Webhooks'
        ])->seeStatusCode(200);
    }

    /**
     * @test
     * @return void
     */
    public function habits_are_saved(): void
    {
        $this->post('/', $this->body, [
            'User-Agent' => 'Todoist-Webhooks'
        ])->seeStatusCode(200);

        $this->seeInDatabase('habits', [
            't_id' => 2766735392,
            'content' => 'habit [day 2]',
            'date_string' => 'ev workday 8:30pm',
            'due_date' => 'Fri 28 Sep 2018 10:30:00 +0000'
        ]);
    }

    /**
     * @test
     * @return void
     */
    public function tasks_are_ignored(): void
    {
        $this->body['event_data']['content'] = 'task';

        $this->post('/', $this->body, [
            'User-Agent' => 'Todoist-Webhooks'
        ])->seeStatusCode(200);

        $this->notSeeInDatabase('habits', [
            't_id' => 2766735392,
            'content' => 'task',
            'date_string' => 'ev workday 8:30pm',
            'due_date' => 'Fri 28 Sep 2018 10:30:00 +0000'
        ]);
    }

    /**
     * @test
     * @return void
     */
    public function updated_habit_with_different_due_date_resets(): void
    {
        $habit = factory(App\Models\Habit::class)->create([
            't_id' => $this->body['event_data']['id'],
            'content' => $this->body['event_data']['content'],
            // older date
            'due_date' => 'Weds 26 Sep 2018 01:00:00 +0000'
        ]);

        // request to Todoist
        $this->mc->expects('request')->once();

        $this->post('/', $this->body, [
            'User-Agent' => 'Todoist-Webhooks'
        ])->seeStatusCode(200);

        $this->seeInDatabase('habits', [
            't_id' => $habit->t_id,
            // day resets
            'content' => '!!test!! habit [day 0]',
            'date_string' => $habit->date_string,
            'due_date' => 'Fri 28 Sep 2018 10:30:00 +0000'
        ]);
    }
}
