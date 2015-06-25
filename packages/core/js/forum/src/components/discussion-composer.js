import ItemList from 'flarum/utils/item-list';
import ComposerBody from 'flarum/components/composer-body';
import Alert from 'flarum/components/alert';
import ActionButton from 'flarum/components/action-button';

/**
  The composer body for starting a new discussion. Adds a text field as a
  control so the user can enter the title of their discussion. Also overrides
  the `submit` and `willExit` actions to account for the title.
 */
export default class DiscussionComposer extends ComposerBody {
  constructor(props) {
    props.placeholder = props.placeholder || 'Write a Post...';
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
      oninput: m.withAttr('value', this.title),
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
      },
      onkeydown: (e) => {
        if (e.which === 13) { // return
          e.preventDefault();
          this.editor.setSelectionRange(0, 0);
        }
        m.redraw.strategy('none');
      }
    })));

    return items;
  }

  onload(element, isInitialized, context) {
    super.onload(element, isInitialized, context);

    this.editor.$('textarea').keydown((e) => {
      if (e.which === 8 && e.target.selectionStart == 0 && e.target.selectionEnd == 0) { // Backspace
        e.preventDefault();
        var title = this.$(':input:enabled:visible:first')[0];
        title.focus();
        title.selectionStart = title.selectionEnd = title.value.length;
      }
    });
  }

  preventExit() {
    return (this.title() || this.content()) && this.props.confirmExit;
  }

  data() {
    return {
      title: this.title(),
      content: this.content()
    };
  }

  onsubmit() {
    this.loading(true);
    m.redraw();

    var data = this.data();

    app.store.createRecord('discussions').save(data).then(discussion => {
      app.composer.hide();
      app.cache.discussionList.addDiscussion(discussion);
      m.route(app.route('discussion', { id: discussion.id(), slug: discussion.slug() }));
    }, response => {
      this.loading(false);
      m.redraw();
    });
  }
}
