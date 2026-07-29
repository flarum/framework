import type Discussion from 'flarum/common/models/Discussion';
import type Post from 'flarum/common/models/Post';
import type User from 'flarum/common/models/User';
import type Tag from 'ext:flarum/tags/common/models/Tag';
/**
 * The resource URL builders attached to `app.route`. Core attaches `discussion`, `post` and
 * `user` to the forum app at runtime; `tag` is added by the tags extension, and addForumRoutes()
 * backfills all of them on the admin app. None of these are present on the base `app.route` type,
 * so we expose a single narrowly-typed accessor instead of casting at every call site.
 */
export interface ResourceRoutes {
    discussion(discussion: Discussion, near?: number): string;
    post(post: Post): string;
    user(user: User): string;
    tag(tag: Tag): string;
}
export default function routes(): ResourceRoutes;
