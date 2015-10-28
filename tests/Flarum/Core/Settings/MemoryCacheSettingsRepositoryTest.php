<?php
namespace Tests\Flarum\Core\Settings;

use Flarum\Settings\MemoryCacheSettingsRepository;
use Flarum\Settings\SettingsRepositoryInterface;
use Mockery as m;
use Tests\Test\TestCase;

class MemoryCacheSettingsRepositoryTest extends TestCase
{
    private $baseRepository;
    private $repository;

    public function init()
    {
        $this->baseRepository = m::mock(SettingsRepositoryInterface::class);
        $this->repository = new MemoryCacheSettingsRepository($this->baseRepository);
    }
    
    public function test_it_should_return_all_settings_when_not_cached()
    {
        $this->baseRepository->shouldReceive('all')->once()->andReturn(['key' => 'value']);

        $this->assertEquals(['key' => 'value'], $this->repository->all());
        $this->assertEquals(['key' => 'value'], $this->repository->all()); // Assert twice to ensure we hit the cache
    }

    public function test_it_should_retrieve_a_specific_value()
    {
        $this->baseRepository->shouldReceive('all')->once()->andReturn(['key1' => 'value1', 'key2' => 'value2']);

        $this->assertEquals('value2', $this->repository->get('key2'));
        $this->assertEquals('value2', $this->repository->get('key2')); // Assert twice to ensure we hit the cache
    }
    
    public function test_it_should_set_a_key_value_pair()
    {
        $this->baseRepository->shouldReceive('set')->once();

        $this->repository->set('key', 'value');

        $this->assertEquals('value', $this->repository->get('key'));
    }
}
