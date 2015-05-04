import Component from 'flarum/component';
import icon from 'flarum/helpers/icon';
import username from 'flarum/helpers/username';
import humanTime from 'flarum/utils/human-time';
import SubtreeRetainer from 'flarum/utils/subtree-retainer';
import ItemList from 'flarum/utils/item-list';
import ActionButton from 'flarum/components/action-button';
import DropdownButton from 'flarum/components/dropdown-button';

export default class PostDiscussionMoved extends Component {
  constructor(props) {
    super(props);

    this.subtree = new SubtreeRetainer(
      () => this.props.post.freshness,
      () => this.props.post.user().freshness
    );
  }

  view(ctrl) {
    var controls = this.controlItems().toArray();

    var post = this.props.post;
    var oldCategory = app.store.getById('categories', post.content()[0]);
    var newCategory = app.store.getById('categories', post.content()[1]);

    return m('article.post.post-activity.post-discussion-moved', this.subtree.retain() || m('div', [
      controls.length ? DropdownButton.component({
        items: controls,
        className: 'contextual-controls',
        buttonClass: 'btn btn-default btn-icon btn-sm btn-naked',
        menuClass: 'pull-right'
      }) : '',
      icon('arrow-right post-icon'),
      m('div.post-activity-info', [
        m('a.post-user', {href: app.route('user', {username: post.user().username()}), config: m.route}, username(post.user())),
        ' moved the discussion from ', m('span.category', {style: {color: oldCategory.color()}}, oldCategory.title()), ' to ', m('span.category', {style: {color: newCategory.color()}}, newCategory.title()), '.'
      ]),
      m('div.post-activity-time', humanTime(post.time()))
    ]));
  }

  controlItems() {
    var items = new ItemList();
    var post = this.props.post;

    if (post.canDelete()) {
      items.add('delete', ActionButton.component({ icon: 'times', label: 'Delete', onclick: this.delete.bind(this) }));
    }

    return items;
  }

  delete() {
    var post = this.props.post;
    post.delete();
    this.props.ondelete && this.props.ondelete(post);
  }
}
