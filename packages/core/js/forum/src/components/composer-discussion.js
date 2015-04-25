import ItemList from 'flarum/utils/item-list';
import ComposerBody from 'flarum/components/composer-body';
import Alert from 'flarum/components/alert';
import ActionButton from 'flarum/components/action-button';

/**
  The composer body for starting a new discussion. Adds a text field as a
  control so the user can enter the title of their discussion. Also overrides
  the `submit` and `willExit` actions to account for the title.
 */
export default class ComposerDiscussion extends ComposerBody {
  constructor(props) {
    props.submitLabel = props.submitLabel || 'Post Discussion';
    props.confirmExit = props.confirmExit || 'You have not posted your discussion. Do you wish to discard it?';
    props.titlePlaceholder = props.titlePlaceholder || 'Discussion Title';

    super(props);

    this.title = m.prop('');
  }

  headerItems() {
    var items = new ItemList();
    var post = this.props.post;

    items.add('title', m('h3', m('input', {
      className: 'form-control',
      value: this.title(),
      onchange: m.withAttr('value', this.title),
      placeholder: this.props.titlePlaceholder,
      disabled: !!this.props.disabled,
      config: function(element, isInitialized) {
        if (isInitialized) { return; }
        $(element).on('input', function() {
          var $this = $(this);
          var empty = !$this.val();
          if (empty) { $this.val($this.attr('placeholder')); }
          $this.css('width', 0);
          $this.css('width', $this[0].scrollWidth);
          if (empty) { $this.val(''); }
        });
        setTimeout(() => $(element).trigger('input'));
      }
    })));

    return items;
  }

  preventExit() {
    return (this.title() || this.content()) && !confirm(this.props.confirmExit);
  }

  onsubmit(content) {
    this.loading(true);
    m.redraw();

    var data = {
      title: this.title(),
      content: content
    };

    app.store.createRecord('discussions').save(data).then(discussion => {
      app.composer.hide();
      app.cache.discussionList.discussions().unshift(discussion);
      m.route(app.route('discussion', discussion));
    }, response => {
      this.loading(false);
      m.redraw();
    });
  }
}
