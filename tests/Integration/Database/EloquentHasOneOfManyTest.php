<?php

namespace Illuminate\Tests\Integration\Database\EloquentHasOneOfManyTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 * @group one-of-many
 */
class EloquentHasOneOfManyTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function ($table) {
            $table->id();
        });

        Schema::create('logins', function ($table) {
            $table->id();
            $table->foreignId('user_id');
        });
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('app.debug', 'true');
    }

    public function testItOnlyEagerLoadsRequiredModels()
    {
        $this->retrievedLogins = 0;
        User::getEventDispatcher()->listen('eloquent.retrieved:*', function ($event, $models) {
            foreach ($models as $model) {
                if (get_class($model) == Login::class) {
                    $this->retrievedLogins++;
                }
            }
        });

        $user = User::create();
        $user->latest_login()->create();
        $user->latest_login()->create();
        $user = User::create();
        $user->latest_login()->create();
        $user->latest_login()->create();

        User::with('latest_login')->get();

        $this->assertSame(2, $this->retrievedLogins);
    }
}

class User extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    public function latest_login()
    {
        return $this->hasOne(Login::class)->ofMany();
    }
}

class Login extends Model
{
    protected $guarded = [];
    public $timestamps = false;
}
