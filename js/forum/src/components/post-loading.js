import Component from 'flarum/component';
import avatar from 'flarum/helpers/avatar';

export default class PostLoadingComponent extends Component {
  view() {
    return m('div.post.comment-post.loading-post.fake-post',
      m('header.post-header', avatar(), m('div.fake-text')),
      m('div.post-body', m('div.fake-text'), m('div.fake-text'), m('div.fake-text'))
    );
  }
}
