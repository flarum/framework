export default function username(user) {
  var username = (user && user.username()) || '[deleted]';

  return m('span.username', username);
}
