<?php

namespace spec\Flarum\Core\Settings;

use Flarum\Core\Settings\SettingsRepository;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class MemoryCacheSettingsRepositorySpec extends ObjectBehavior
{
    function let(SettingsRepository $inner)
    {
        $this->beConstructedWith($inner);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Flarum\Core\Settings\MemoryCacheSettingsRepository');
    }

    function it_retrieves_data_from_inner(SettingsRepository $inner)
    {
        $settings = ['a' => 1, 'b' => 2];
        $inner->all()->willReturn($settings);
        $inner->all()->shouldBeCalled();

        // Test fetching all settings
        $this->all()->shouldReturn($settings);

        // Test fetching single settings
        $this->get('a')->shouldReturn(1);
        $this->get('b')->shouldReturn(2);

        // Test invalid key
        $this->get('c')->shouldReturn(null);

        // Test invalid key with custom default
        $this->get('d', 'foobar')->shouldReturn('foobar');
    }

    function it_passes_new_data_to_inner(SettingsRepository $inner)
    {
        $this->set('a', 1);
        $inner->set('a', 1)->shouldHaveBeenCalled();
    }

    function it_caches_new_data(SettingsRepository $inner)
    {
        $this->set('b', 2);
        $this->get('b')->shouldReturn(2);
        $inner->all()->shouldNotHaveBeenCalled();
        $inner->get('b')->shouldNotHaveBeenCalled();
    }
}
