<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Core\Command\EditUser;
use Illuminate\Contracts\Bus\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class UpdateUserController extends AbstractResourceController
{
    use AssertPermissionTrait;

    /**
     * {@inheritdoc}
     */
    public $serializer = 'Flarum\Api\Serializer\CurrentUserSerializer';

    /**
     * {@inheritdoc}
     */
    public $include = ['groups'];

    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * The Attributes that can be changed without sudo mode.
     *
     * @var array
     */
     protected $noSudoAttributes = ['readtime', 'preferences'];

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $id = array_get($request->getQueryParams(), 'id');
        $actor = $request->getAttribute('actor');
        $data = array_get($request->getParsedBody(), 'data', []);

        foreach ($data['attributes'] as $k => $v) {
            if(!in_array($k, $this->noSudoAttributes)) {
                $this->assertSudo($request);
                break;
            }
        }

        return $this->bus->dispatch(
            new EditUser($id, $actor, $data)
        );
    }
}
