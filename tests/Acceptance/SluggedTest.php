<?php
namespace Tests\Acceptance;

use Tests\Acceptance\Models\Post;
use Tests\Acceptance\Models\User;

class SluggedTest extends AcceptanceTestCase
{
    public function testUserSlug()
    {
        $user = new User;
        $user->first_name = 'Kirk';
        $user->last_name = 'Bushell';
        $user->save();

        $this->assertEquals('kirk-bushell', (string) $user->slug);
    }

    public function testPostSlug()
    {
        $user = new User;
        $user->first_name = 'Kirk';
        $user->last_name = 'Bushell';
        $user->save();

        $post = new Post;
        $post->user()->associate($user);
        $post->visible = true;
        $post->weight = 0;
        $post->save();

        $this->assertRegExp('/^[a-z0-9]{8}$/i', (string) $post->slug);
    }
}
