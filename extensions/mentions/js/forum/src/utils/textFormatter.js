import username from 'flarum/helpers/username';
import extractText from 'flarum/utils/extractText';

export function filterUserMentions(tag) {
  const user = app.store.getBy('users', 'username', tag.getAttribute('username'));

  if (user) {
    tag.setAttribute('id', user.id());
    tag.setAttribute('displayname', extractText(username(user)));

    return true;
  }
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
