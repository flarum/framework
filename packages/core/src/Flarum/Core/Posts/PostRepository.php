<?php namespace Flarum\Core\Posts;

class PostRepository
{
    public function find($id)
    {
        return Post::find($id);
    }

    public function findOrFail($id, $user = null)
    {
        $query = Post::query();
        
        if ($user !== null) {
            $query = $query->whereCanView($user);
        }

        return $query->findOrFail($id);
    }

    public function getIndexForNumber($discussionId, $number)
    {
        return Post::whereCanView()
            ->where('discussion_id', $discussionId)
            ->where('time', '<', function ($query) use ($discussionId, $number) {
                $query->select('time')
                      ->from('posts')
                      ->where('discussion_id', $discussionId)
                      ->whereNotNull('number')
                      ->orderByRaw('ABS(CAST(number AS SIGNED) - ?)', [$number])
                      ->take(1);
            })
            ->count();
    }

    public function findByDiscussion($discussionId, $relations = [], $sortBy = 'time', $sortOrder = 'asc', $count = null, $start = 0)
    {
        return Post::with($relations)
            ->whereCanView()
            ->where('discussion_id', $discussionId)
            ->skip($start)
            ->take($count)
            ->orderBy($sortBy, $sortOrder)
            ->get();
    }

    public function findMany($ids, $relations = [])
    {
        return Post::with($relations)
            ->whereCanView()
            ->whereIn('id', $ids)
            ->get();
    }

    public function save(Post $post)
    {
        $post->assertValid();
        $post->save();
    }

    public function delete(Post $post)
    {
        $post->delete();
    }
}
