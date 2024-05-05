<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Api\Controller;

use Carbon\Carbon;
use Flarum\Api\Controller\AbstractListController;
use Flarum\Flags\Api\Serializer\FlagSerializer;
use Flarum\Flags\Flag;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListFlagsController extends AbstractListController
{
    public ?string $serializer = FlagSerializer::class;

    public array $include = [
        'user',
        'post',
        'post.user',
        'post.discussion'
    ];

    public function __construct(
        protected UrlGenerator $url
    ) {
    }

    protected function data(ServerRequestInterface $request, Document $document): iterable
    {
        $actor = RequestUtil::getActor($request);

        $actor->assertRegistered();

        $actor->read_flags_at = Carbon::now();
        $actor->save();

        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);
        $include = $this->extractInclude($request);

        if (in_array('post.user', $include)) {
            $include[] = 'post.user.groups';
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, Flag> $flags */
        $flags = Flag::whereVisibleTo($actor)
            ->limit($limit + 1)
            ->offset($offset)
            ->whenPgSql(
                fn (Builder $query) => $query->distinct('post_id')->orderBy('post_id'),
                else: fn (Builder $query) => $query->groupBy('post_id')
            )
            ->latest('flags.id')
            ->get();

        $this->loadRelations($flags, $include, $request);

        $flags = $flags->all();

        $areMoreResults = false;

        if (count($flags) > $limit) {
            array_pop($flags);
            $areMoreResults = true;
        }

        $this->addPaginationData(
            $document,
            $request,
            $this->url->to('api')->route('flags.index'),
            $areMoreResults ? null : 0
        );

        return $flags;
    }
}
