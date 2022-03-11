import app from 'flarum/forum/app';
import username from 'flarum/common/helpers/username';
import extractText from 'flarum/common/utils/extractText';

export function filterUserMentions(tag) {
  let user;

  if (app.forum.attribute('allowUsernameMentionFormat') && tag.hasAttribute('username'))
    user = app.store.getBy('users', 'username', tag.getAttribute('username'));
  else if (tag.hasAttribute('id')) user = app.store.getById('users', tag.getAttribute('id'));

  if (user) {
    tag.setAttribute('id', user.id());
    tag.setAttribute('slug', user.slug());
    tag.setAttribute('displayname', extractText(username(user)));

    return true;
  }

  tag.invalidate();
}

export function filterPostMentions(tag) {
  const post = app.store.getById('posts', tag.getAttribute('id'));

  if (post) {
    tag.setAttribute('discussionid', post.discussion().id());
    tag.setAttribute('number', post.number());
    tag.setAttribute('displayname', extractText(username(post.user())));

    return true;
  }
}
