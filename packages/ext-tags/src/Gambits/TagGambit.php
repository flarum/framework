<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Gambits;

use Flarum\Tags\TagRepository;
use Flarum\Core\Search\Search;
use Flarum\Core\Search\RegexGambit;
use Illuminate\Database\Query\Expression;

class TagGambit extends RegexGambit
{
    protected $pattern = 'tag:(.+)';

    /**
     * @var \Flarum\Tags\TagRepository
     */
    protected $tags;

    /**
     * @param \Flarum\Tags\TagRepository $tags
     */
    public function __construct(TagRepository $tags)
    {
        $this->tags = $tags;
    }

    protected function conditions(Search $search, array $matches, $negate)
    {
        $slugs = explode(',', trim($matches[1], '"'));

        // TODO: implement $negate
        $search->getQuery()->where(function ($query) use ($slugs) {
            foreach ($slugs as $slug) {
                if ($slug === 'untagged') {
                    $query->orWhereNotExists(function ($query) {
                        $query->select(app('flarum.db')->raw(1))
                              ->from('discussions_tags')
                              ->where('discussions.id', new Expression('discussion_id'));
                    });
                } else {
                    $id = $this->tags->getIdForSlug($slug);

                    $query->orWhereExists(function ($query) use ($id) {
                        $query->select(app('flarum.db')->raw(1))
                              ->from('discussions_tags')
                              ->where('discussions.id', new Expression('discussion_id'))
                              ->where('tag_id', $id);
                    });
                }
            }
        });
    }
}
