<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Resource;

use Flarum\Api\Endpoint;
use Flarum\Api\Resource\Contracts\Findable;
use Flarum\Api\Schema;
use Flarum\Foundation\Console\InfoCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tobyz\JsonApiServer\Context;

/**
 * @extends AbstractResource<object>
 */
class SystemInfoResource extends AbstractResource implements Findable
{
    public function __construct(
        protected InfoCommand $infoCommand
    ) {
    }

    public function type(): string
    {
        return 'system-info';
    }

    public function getId(object $model, Context $context): string
    {
        return 'system';
    }

    public function id(Context $context): ?string
    {
        return 'system';
    }

    public function find(string $id, Context $context): ?object
    {
        return (object) [];
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Show::make()
                ->admin(),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('content')
                ->get(function () {
                    $output = new BufferedOutput();

                    $this->infoCommand->run(
                        new ArrayInput([]),
                        $output
                    );

                    return $output->fetch();
                }),
        ];
    }
}
