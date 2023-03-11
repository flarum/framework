import app from 'flarum/forum/app';
import MentionableModel from './MentionableModel';
import type Post from 'flarum/common/models/Post';
import type Mithril from 'mithril';
import usernameHelper from 'flarum/common/helpers/username';
import avatar from 'flarum/common/helpers/avatar';
import highlight from 'flarum/common/helpers/highlight';
import { truncate } from 'flarum/common/utils/string';
import ReplyComposer from 'flarum/forum/components/ReplyComposer';
import EditPostComposer from 'flarum/forum/components/EditPostComposer';
import getCleanDisplayName from '../utils/getCleanDisplayName';
import type AtMentionFormat from './formats/AtMentionFormat';

export default class PostMention extends MentionableModel<Post, AtMentionFormat> {
  type(): string {
    return 'post';
  }

  /**
   * If the user is replying to a discussion, or if they are editing a
   * post, then we can suggest other posts in the discussion to mention.
   * We will add the 5 most recent comments in the discussion which
   * match any username characters that have been typed.
   */
  initialResults(): Post[] {
    if (!app.composer.bodyMatches(ReplyComposer) && !app.composer.bodyMatches(EditPostComposer)) {
      return [];
    }

    // @ts-ignore
    const composerAttrs = app.composer.body.attrs;
    const composerPost = composerAttrs.post;
    const discussion = (composerPost && composerPost.discussion()) || composerAttrs.discussion;

    return (
      discussion
        .posts()
        // Filter to only comment posts, and replies before this message
        .filter((post: Post) => post && post.contentType() === 'comment' && (!composerPost || post.number() < composerPost.number()))
        // Sort by new to old
        .sort((a: Post, b: Post) => b.createdAt().getTime() - a.createdAt().getTime())
    );
  }

  /**
   * Generates the syntax for mentioning of a post. Also cleans up the display name.
   *
   * @example <caption>Post mention</caption>
   * // '@"User"#p13'
   * // @"Display name"#pPostID
   * forPostMention(user, 13) // User display name is 'User', post ID is 13
   */
  public replacement(post: Post): string {
    const user = post.user();
    const cleanText = getCleanDisplayName(user);
    return this.format.format(cleanText, 'p', post.id());
  }

  suggestion(model: Post, typed: string): Mithril.Children {
    const user = model.user() || null;
    const username = usernameHelper(user);

    if (typed) {
      username.children = [highlight((username.text ?? '') as string, typed)];
      delete username.text;
    }

    return (
      <>
        {avatar(user)}
        {username}
        {[
          app.translator.trans('flarum-mentions.forum.composer.reply_to_post_text', { number: model.number() }),
          ' — ',
          truncate(model.contentPlain() ?? '', 200),
        ]}
      </>
    );
  }

  matches(model: Post, typed: string): boolean {
    const user = model.user();
    const userMentionable = app.mentionFormats.mentionable('user')!;

    return !typed || (user && userMentionable.matches(user, typed));
  }

  maxStoreMatchedResults(): number {
    return 5;
  }

  /**
   * Post mention suggestions are only offered from current discussion posts.
   */
  search(typed: string): Promise<Post[]> {
    return Promise.resolve([]);
  }

  enabled(): boolean {
    return true;
  }
}
