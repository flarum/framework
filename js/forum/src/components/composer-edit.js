import ItemList from 'flarum/utils/item-list';
import ComposerBody from 'flarum/components/composer-body';
import Alert from 'flarum/components/alert';
import ActionButton from 'flarum/components/action-button';

/**
  The composer body for editing a post. Sets the initial content to the
  content of the post that is being edited, and adds a title control to
  indicate which post is being edited.
 */
export default class ComposerEdit extends ComposerBody {
  constructor(props) {
    props.submitLabel = props.submitLabel || 'Save Changes';
    props.confirmExit = props.confirmExit || 'You have not saved your changes. Do you wish to discard them?';
    props.originalContent = props.originalContent || props.post.content();
    props.user = props.user || props.post.user();

    super(props);
  }

  headerItems() {
    var items = new ItemList();
    var post = this.props.post;

    items.add('title', m('h3', [
      'Editing ',
      m('a', {href: app.route.discussion(post.discussion(), post.number()), config: m.route}, 'Post #'+post.number()),
      ' in ', post.discussion().title()
    ]));

    return items;
  }

  data() {
    return {
      content: this.content()
    };
  }

  onsubmit() {
    var post = this.props.post;

    this.loading(true);
    m.redraw();

    post.save(this.data()).then(post => {
      app.composer.hide();
      m.redraw();
    }, response => {
      this.loading(false);
      m.redraw();
    });
  }
}
