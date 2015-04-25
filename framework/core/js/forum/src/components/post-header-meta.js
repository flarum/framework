import Component from 'flarum/component';
import humanTime from 'flarum/helpers/human-time';
import fullTime from 'flarum/helpers/full-time';

/**
  Component for the meta part of a post header. Displays the time, and when
  clicked, shows a dropdown containing more information about the post
  (number, full time, permalink).
 */
export default class PostHeaderMeta extends Component {
  view() {
    var post = this.props.post;
    var discussion = post.discussion();

    var params = {
      id: discussion.id(),
      slug: discussion.slug(),
      near: post.number()
    };
    var permalink = window.location.origin+app.route('discussion.near', params);
    var touch = 'ontouchstart' in document.documentElement;

    // When the dropdown menu is shown, select the contents of the permalink
    // input so that the user can quickly copy the URL.
    var selectPermalink = function() {
      var input = $(this).parent().find('.permalink');
      setTimeout(() => input.select());
      m.redraw.strategy('none');
    }

    return m('span.dropdown',
      m('a.dropdown-toggle[href=javascript:;][data-toggle=dropdown]', {onclick: selectPermalink}, humanTime(post.time())),
      m('div.dropdown-menu.post-meta', [
        m('span.number', 'Post #'+post.number()),
        m('span.time', fullTime(post.time())),
        touch
          ? m('a.btn.btn-default.permalink', {href: permalink}, permalink)
          : m('input.form-control.permalink', {value: permalink, onclick: (e) => e.stopPropagation()})
      ])
    );
  }
}
