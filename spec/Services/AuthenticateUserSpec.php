<?php

namespace spec\App\Services;

use App\Models\User;
use Prophecy\Argument;
use PhpSpec\ObjectBehavior;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Auth\Guard;
use Laravel\Socialite\Contracts\Factory;
use Laravel\Socialite\Two\ProviderInterface;
use App\Contracts\Auth\AuthenticateUserListener;

class AuthenticateUserSpec extends ObjectBehavior
{
    const HAS_CODE = true;

    const HAS_NO_CODE = false;

    function let(Factory $socialite, Guard $auth, UserRepository $users)
    {
        $this->beConstructedWith($socialite, $auth, $users);
    }

    function it_authorizes_a_user(
        Factory $socialite,
        ProviderInterface $provider,
        AuthenticateUserListener $listener
    )
    {
        $provider->redirect()->shouldBeCalled();
        $socialite->driver('github')->willReturn($provider);

        $this->execute(self::HAS_NO_CODE, $listener);
    }

    function it_creates_a_user_if_authorization_is_granted(
        Factory $socialite,
        UserRepository $users,
        Guard $auth,
        User $user,
        AuthenticateUserListener $listener
    )
    {
        $socialite->driver('github')->willReturn(new ProviderStub);
        $users->findByUsernameOrCreate(ProviderStub::$data)->willReturn($user);
        $auth->login($user, self::HAS_CODE)->shouldBeCalled();
        $listener->userHasLoggedIn($user)->shouldBeCalled();

        $this->execute(self::HAS_CODE, $listener);
    }
}

class ProviderStub
{
    public static $data = [
        'id' => 1,
        'nickname' => 'foo',
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'avatar' => 'foo.jpg'
    ];

    public function user()
    {
        return self::$data;
    }
}