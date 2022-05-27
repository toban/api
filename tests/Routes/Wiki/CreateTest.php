<?php

namespace Tests\Routes\Wiki\Managers;

use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\User;
use Illuminate\Support\Facades\Queue;
use App\Jobs\CirrusSearch\ElasticSearchIndexInit;
use App\Jobs\ProvisionWikiDbJob;
use App\Jobs\MediawikiInit;
use App\WikiSetting;
use App\WikiManager;
use App\Wiki;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class CreateTest extends TestCase
{
    protected $route = 'wiki/create';

    use OptionsRequestAllowed;
    use DatabaseTransactions;

    public function testWikiCreateDispatchesSomeJobs()
    {
        Queue::fake();

        $user = User::factory()->create(['verified' => true]);
        Queue::assertNothingPushed();

        $response = $this->actingAs($user, 'api')
        ->json(
            'POST', 
            $this->route, 
            [
                'domain' => 'derp.com',
                'sitename' => 'merp',
                'username' => 'AdminBoss'
            ]
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.domain', 'derp.com')
            ->assertJsonPath('data.name', null)
            ->assertJsonPath('success', true );

        Queue::assertPushed( ProvisionWikiDbJob::class, 1);
        Queue::assertPushed( MediawikiInit::class, 1);
        Queue::assertPushed( ElasticSearchIndexInit::class, 1);
        
        $id = $response->original['data']['id'];

        $this->assertSame(
            1, 
            WikiSetting::where( [ 'name' => WikiSetting::wgSecretKey, 'wiki_id' => $id ] )->count()
        );

        $this->assertSame(
            1, 
            WikiSetting::where( [ 'name' => WikiSetting::wwExtEnableElasticSearch, 'value' => true, 'wiki_id' => $id ] )->count()
        );
    }

    public function testCreateWikiLimitsNumWikisPerUser()
    {
        Config::set('wbstack.wiki_max_per_user', 1);

        Queue::fake();

        $user = User::factory()->create(['verified' => true]);
        Queue::assertNothingPushed();

        // This shouldn't stop first create since it's deleted
        $this->wiki = Wiki::factory()->create( [ 'deleted_at' => Carbon::now()->timestamp ] );
        WikiManager::factory()->create(['wiki_id' => $this->wiki->id, 'user_id' => $user->id]);

        $response = $this->actingAs($user, 'api')
        ->json(
            'POST', 
            $this->route, 
            [
                'domain' => 'mywikidomain.com',
                'sitename' => 'merp',
                'username' => 'AdminBoss'
            ]
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.domain', 'mywikidomain.com')
            ->assertJsonPath('success', true );

        $response = $this->actingAs($user, 'api')
            ->json(
                'POST', 
                $this->route, 
                [
                    'domain' => 'mywikidomain-2.com',
                    'sitename' => 'merp',
                    'username' => 'AdminBoss'
                ]
            );
        $response->assertStatus(403)
            ->assertJsonPath('message', 'Too many wikis. Your new total of 2 would exceed the limit of 1 per user.');
    }
}
