<?php namespace Flarum\Categories;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\Api\Actions\Discussions\IndexAction;
use Flarum\Api\Actions\Discussions\ShowAction;
use Flarum\Core\Models\Post;
use Flarum\Core\Models\Discussion;

class CategoriesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(Dispatcher $events)
    {
        $events->subscribe('Flarum\Categories\Handlers\Handler');

        IndexAction::$include['category'] = true;

        ShowAction::$include['category'] = true;

        Post::addType('discussionMoved', 'Flarum\Categories\DiscussionMovedPost');

        Discussion::addRelationship('category', function ($model) {
            return $model->belongsTo('Flarum\Categories\Category', null, null, 'category');
        });
    }

    public function register()
    {
        //
    }
}
