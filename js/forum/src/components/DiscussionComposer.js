import ComposerBody from 'flarum/components/ComposerBody';

/**
 * The `DiscussionComposer` component displays the composer content for starting
 * a new discussion. It adds a text field as a header control so the user can
 * enter the title of their discussion. It also overrides the `submit` and
 * `willExit` actions to account for the title.
 *
 * ### Props
 *
 * - All of the props for ComposerBody
 * - `titlePlaceholder`
 */
export default class DiscussionComposer extends ComposerBody {
  constructor(...args) {
    super(...args);

    /**
     * The value of the title input.
     *
     * @type {Function}
     */
    this.title = m.prop('');
  }

  static initProps(props) {
    super.initProps(props);

    props.placeholder = props.placeholder || app.trans('core.write_a_post');
    props.submitLabel = props.submitLabel || app.trans('core.post_discussion');
    props.confirmExit = props.confirmExit || app.trans('core.confirm_discard_discussion');
    props.titlePlaceholder = props.titlePlaceholder || app.trans('core.discussion_title');
  }

  headerItems() {
    const items = super.headerItems();

    items.add('title', (
      <h3>
        <input className="FormControl"
          value={this.title()}
          oninput={m.withAttr('value', this.title)}
          placeholder={this.props.titlePlaceholder}
          disabled={!!this.props.disabled}
          onkeydown={this.onkeydown.bind(this)}/>
      </h3>
    ));

    return items;
  }

  /**
   * Handle the title input's keydown event. When the return key is pressed,
   * move the focus to the start of the text editor.
   *
   * @param {Event} e
   */
  onkeydown(e) {
    if (e.which === 13) { // Return
      e.preventDefault();
      this.editor.setSelectionRange(0, 0);
    }

    m.redraw.strategy('none');
  }

  config(isInitialized, context) {
    super.config(isInitialized, context);

    // If the user presses the backspace key in the text editor, and the cursor
    // is already at the start, then we'll move the focus back into the title
    // input.
    this.editor.$('textarea').keydown((e) => {
      if (e.which === 8 && e.target.selectionStart === 0 && e.target.selectionEnd === 0) {
        e.preventDefault();

        const $title = this.$(':input:enabled:visible:first')[0];
        $title.focus();
        $title.selectionStart = $title.selectionEnd = $title.value.length;
      }
    });
  }

  preventExit() {
    return (this.title() || this.content()) && this.props.confirmExit;
  }

  /**
   * Get the data to submit to the server when the discussion is saved.
   *
   * @return {Object}
   */
  data() {
    return {
      title: this.title(),
      content: this.content()
    };
  }

  onsubmit() {
    this.loading = true;

    const data = this.data();

    app.store.createRecord('discussions').save(data).then(
      discussion => {
        app.composer.hide();
        app.cache.discussionList.addDiscussion(discussion);
        m.route(app.route.discussion(discussion));
      },
      response => {
        this.loading = false;
        m.redraw();
        app.alertErrors(response.errors);
      }
    );
  }
}
