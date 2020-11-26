import ComposerBody from './ComposerBody';
import extractText from '../../common/utils/extractText';
import Stream from '../../common/utils/Stream';

/**
 * The `DiscussionComposer` component displays the composer content for starting
 * a new discussion. It adds a text field as a header control so the user can
 * enter the title of their discussion. It also overrides the `submit` and
 * `willExit` actions to account for the title.
 *
 * ### Attrs
 *
 * - All of the attrs for ComposerBody
 * - `titlePlaceholder`
 */
export default class DiscussionComposer extends ComposerBody {
  static initAttrs(attrs) {
    super.initAttrs(attrs);

    attrs.placeholder = attrs.placeholder || extractText(app.translator.trans('core.forum.composer_discussion.body_placeholder'));
    attrs.submitLabel = attrs.submitLabel || app.translator.trans('core.forum.composer_discussion.submit_button');
    attrs.confirmExit = attrs.confirmExit || extractText(app.translator.trans('core.forum.composer_discussion.discard_confirmation'));
    attrs.titlePlaceholder = attrs.titlePlaceholder || extractText(app.translator.trans('core.forum.composer_discussion.title_placeholder'));
    attrs.className = 'ComposerBody--discussion';
  }

  oninit(vnode) {
    super.oninit(vnode);

    this.composer.fields.title = this.composer.fields.title || Stream('');

    /**
     * The value of the title input.
     *
     * @type {Function}
     */
    this.title = this.composer.fields.title;
  }

  headerItems() {
    const items = super.headerItems();

    items.add('title', <h3>{app.translator.trans('core.forum.composer_discussion.title')}</h3>, 100);

    items.add(
      'discussionTitle',
      <h3>
        <input
          className="FormControl"
          bidi={this.title}
          placeholder={this.attrs.titlePlaceholder}
          disabled={!!this.attrs.disabled}
          onkeydown={this.onkeydown.bind(this)}
        />
      </h3>
    );

    return items;
  }

  /**
   * Handle the title input's keydown event. When the return key is pressed,
   * move the focus to the start of the text editor.
   *
   * @param {Event} e
   */
  onkeydown(e) {
    if (e.which === 13) {
      // Return
      e.preventDefault();
      this.composer.editor.moveCursorTo(0);
    }

    e.redraw = false;
  }

  hasChanges() {
    return this.title() || this.composer.fields.content();
  }

  /**
   * Get the data to submit to the server when the discussion is saved.
   *
   * @return {Object}
   */
  data() {
    return {
      title: this.title(),
      content: this.composer.fields.content(),
    };
  }

  onsubmit() {
    this.loading = true;

    const data = this.data();

    app.store
      .createRecord('discussions')
      .save(data)
      .then((discussion) => {
        this.composer.hide();
        app.discussions.refresh({ deferClear: true });
        m.route.set(app.route.discussion(discussion));
      }, this.loaded.bind(this));
  }
}
