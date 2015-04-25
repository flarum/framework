import Component from 'flarum/component';
import classList from 'flarum/utils/class-list';
import ComposerEdit from 'flarum/components/composer-edit';
import PostHeaderUser from 'flarum/components/post-header-user';
import PostHeaderMeta from 'flarum/components/post-header-meta';
import PostHeaderEdited from 'flarum/components/post-header-edited';
import PostHeaderToggle from 'flarum/components/post-header-toggle';
import ItemList from 'flarum/utils/item-list';
import ActionButton from 'flarum/components/action-button';
import DropdownButton from 'flarum/components/dropdown-button';
import SubtreeRetainer from 'flarum/utils/subtree-retainer';
import listItems from 'flarum/helpers/list-items';

/**
  Component for a `comment`-typed post. Displays a number of item lists
  (controls, header, and footer) surrounding the post's HTML content. Allows
  the post to be edited with the composer, hidden, or restored.
 */
export default class PostComment extends Component {
  constructor(props) {
    super(props);

    this.postHeaderUser = new PostHeaderUser({post: this.props.post});

    this.subtree = new SubtreeRetainer(
      () => this.props.post.freshness,
      () => this.props.post.user().freshness,
      this.postHeaderUser.showCard
    );
  }

  view() {
    var post = this.props.post;

    var classes = {
      'is-hidden': post.isHidden(),
      'is-edited': post.isEdited(),
      'reveal-content': this.revealContent
    };

    var controls = this.controlItems().toArray();

    // @todo Having to wrap children in a div isn't nice
    return m('article.post.post-comment', {className: classList(classes)}, this.subtree.retain() || m('div', [
      controls.length ? DropdownButton.component({
        items: controls,
        className: 'contextual-controls',
        buttonClass: 'btn btn-default btn-icon btn-sm btn-naked',
        menuClass: 'pull-right'
      }) : '',
      m('header.post-header', m('ul', listItems(this.headerItems().toArray()))),
      m('div.post-body', m.trust(post.contentHtml())),
      m('aside.post-footer', m('ul', listItems(this.footerItems().toArray()))),
      m('aside.post-actions', m('ul', listItems(this.actionItems().toArray())))
    ]));
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

  controlItems() {
    var items = new ItemList();
    var post = this.props.post;

    if (post.isHidden()) {
      if (post.canEdit()) {
        items.add('restore', ActionButton.component({ icon: 'reply', label: 'Restore', onclick: this.restore.bind(this) }));
      }
      if (post.canDelete()) {
        items.add('delete', ActionButton.component({ icon: 'times', label: 'Delete Forever', onclick: this.delete.bind(this) }));
      }
    } else if (post.canEdit()) {
      items.add('edit', ActionButton.component({ icon: 'pencil', label: 'Edit', onclick: this.edit.bind(this) }));
      items.add('hide', ActionButton.component({ icon: 'times', label: 'Delete', onclick: this.hide.bind(this) }));
    }

    return items;
  }

  footerItems() {
    return new ItemList();
  }

  actionItems() {
    return new ItemList();
  }

  edit() {
    if (!this.composer || app.composer.component !== this.composer) {
      this.composer = new ComposerEdit({ post: this.props.post });
      app.composer.load(this.composer);
    }
    app.composer.show();
  }

  hide() {
    var post = this.props.post;
    post.save({ isHidden: true });
    post.pushData({ hideTime: new Date(), hideUser: app.session.user() });
  }

  restore() {
    var post = this.props.post;
    post.save({ isHidden: false });
    post.pushData({ hideTime: null, hideUser: null });
  }

  delete() {
    var post = this.props.post;
    post.delete();
    this.props.ondelete && this.props.ondelete(post);
  }
}
