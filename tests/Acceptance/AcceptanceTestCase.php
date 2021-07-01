<?php
namespace Tests\Acceptance;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class AcceptanceTestCase extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->init();
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'test');
        $app['config']->set('database.connections.test', array(
            'driver'   => 'sqlite',
            'database' => ':memory:'
        ));
    }

    protected function init()
    {
        // Overload
    }

    private function migrate()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('slug')->nullable();
            $table->integer('comment_count')->default(0)->nullable();
            $table->integer('post_count')->default(0)->nullable();
            $table->integer('post_count_explicit')->default(0)->nullable();
            $table->integer('post_count_conditional')->default(0)->nullable();
            $table->integer('post_count_complex_conditional')->default(0)->nullable();
            $table->integer('post_comment_sum')->default(0)->nullable();
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->nullable();
            $table->string('slug')->nullable();
            $table->integer('comment_count')->default(0)->nullable();
            $table->boolean('visible')->default(false);
            $table->integer('weight')->default(0);
            $table->timestamp('first_commented_at')->nullable();
            $table->timestamp('last_commented_at')->nullable();
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('post_id');
            $table->boolean('visible')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('item_total')->default(0)->nullable();
            $table->integer('item_total_explicit')->default(0)->nullable();
            $table->integer('item_total_conditional')->default(0)->nullable();
            $table->integer('item_total_complex_conditional')->default(0)->nullable();
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('order_id');
            $table->integer('total');
            $table->boolean('billable')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
