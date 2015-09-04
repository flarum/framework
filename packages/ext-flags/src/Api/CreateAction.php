<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Reports\Api;

use Flarum\Reports\Commands\CreateReport;
use Flarum\Api\Actions\CreateAction as BaseCreateAction;
use Flarum\Api\JsonApiRequest;
use Illuminate\Contracts\Bus\Dispatcher;

class CreateAction extends BaseCreateAction
{
    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @inheritdoc
     */
    public $serializer = 'Flarum\Reports\Api\ReportSerializer';

    /**
     * @inheritdoc
     */
    public $include = [
        'post' => true,
        'post.reports' => true
    ];

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * Create a report according to input from the API request.
     *
     * @param JsonApiRequest $request
     * @return \Flarum\Reports\Report
     */
    protected function create(JsonApiRequest $request)
    {
        return $this->bus->dispatch(
            new CreateReport($request->actor, $request->get('data'))
        );
    }
}
