import FormModal from 'flarum/components/form-modal';
import avatar from 'flarum/helpers/avatar';
import username from 'flarum/helpers/username';

export default class PostLikesModal extends FormModal {
  view() {
    var post = this.props.post;

    return super.view({
      className: 'post-likes-modal',
      title: 'Users Who Like This',
      body: [
        m('ul.post-likes-list', [
          post.likes().map(user =>
            m('li', m('a', {href: app.route.user(user), config: m.route}, [
              avatar(user),
              username(user)
            ]))
          )
        ])
      ]
    });
  }
}
