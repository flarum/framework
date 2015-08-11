<?php namespace Flarum\Install;

use Illuminate\Database\Schema\Builder;

abstract class Migration
{
    /**
     * @var Builder
     */
    protected $schema;

    public function __construct(Builder $builder)
    {
        $this->schema = $builder;
    }
}
