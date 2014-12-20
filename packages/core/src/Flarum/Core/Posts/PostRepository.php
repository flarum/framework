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
