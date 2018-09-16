<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Gambit;

use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\AbstractSearch;
use Flarum\Tags\TagRepository;

class TagGambit extends AbstractRegexGambit
{
    /**
     * {@inheritdoc}
     */
    protected $pattern = 'tag:(.+)';

    /**
     * @var TagRepository
     */
    protected $tags;

    /**
     * @param TagRepository $tags
     */
    public function __construct(TagRepository $tags)
    {
        $this->tags = $tags;
    }

    /**
     * {@inheritdoc}
     */
    protected function conditions(AbstractSearch $search, array $matches, $negate)
    {
        $slugs = explode(',', trim($matches[1], '"'));

        $search->getQuery()->where(function ($query) use ($slugs, $negate) {
            foreach ($slugs as $slug) {
                if ($slug === 'untagged') {
                    $query->orWhereExists(function ($query) {
                        $query->selectRaw('1')
                              ->from('discussion_tag')
                              ->whereColumn('discussions.id', 'discussion_id');
                    }, ! $negate);
                } else {
                    $id = $this->tags->getIdForSlug($slug);

                    $query->orWhereExists(function ($query) use ($id) {
                        $query->selectRaw('1')
                              ->from('discussion_tag')
                              ->whereColumn('discussions.id', 'discussion_id')
                              ->where('tag_id', $id);
                    }, $negate);
                }
            }
        });
    }
}
