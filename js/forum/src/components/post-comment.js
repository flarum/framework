import Post from 'flarum/components/post';
import classList from 'flarum/utils/class-list';
import PostHeaderUser from 'flarum/components/post-header-user';
import PostHeaderMeta from 'flarum/components/post-header-meta';
import PostHeaderEdited from 'flarum/components/post-header-edited';
import PostHeaderToggle from 'flarum/components/post-header-toggle';
import ItemList from 'flarum/utils/item-list';
import listItems from 'flarum/helpers/list-items';

/**
  Component for a `comment`-typed post. Displays a number of item lists
  (controls, header, and footer) surrounding the post's HTML content. Allows
  the post to be edited with the composer, hidden, or restored.
 */
export default class PostComment extends Post {
  constructor(props) {
    super(props);

    this.postHeaderUser = new PostHeaderUser({post: this.props.post});
    this.subtree.check(this.postHeaderUser.showCard);
  }

  view() {
    var post = this.props.post;

    return super.view([
      m('header.post-header', m('ul', listItems(this.headerItems().toArray()))),
      m('div.post-body', m.trust(post.contentHtml())),
      m('aside.post-footer', m('ul', listItems(this.footerItems().toArray()))),
      m('aside.post-actions', m('ul', listItems(this.actionItems().toArray())))
    ], {
      className: classList({
        'post-comment': true,
        'is-hidden': post.isHidden(),
        'is-edited': post.isEdited(),
        'reveal-content': this.revealContent
      })
    });
  }

  toggleContent() {
    this.revealContent = !this.revealContent;
  }

  headerItems() {
    var items = new ItemList();
    var post = this.props.post;
    var props = {post};

    items.add('user', this.postHeaderUser.view(), {first: true});
    items.add('meta', PostHeaderMeta.component(props));

    if (post.isEdited() && !post.isHidden()) {
      items.add('edited', PostHeaderEdited.component(props));
    }

    if (post.isHidden()) {
      items.add('toggle', PostHeaderToggle.component({toggle: this.toggleContent.bind(this)}));
    }

    return items;
  }

  footerItems() {
    return new ItemList();
  }

  actionItems() {
    return new ItemList();
  }
}
