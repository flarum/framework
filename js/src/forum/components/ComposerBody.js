import Component from '../../common/Component';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import ConfirmDocumentUnload from '../../common/components/ConfirmDocumentUnload';
import TextEditor from '../../common/components/TextEditor';
import avatar from '../../common/helpers/avatar';
import listItems from '../../common/helpers/listItems';
import ItemList from '../../common/utils/ItemList';
import classList from '../../common/utils/classList';

/**
 * The `ComposerBody` component handles the body, or the content, of the
 * composer. Subclasses should implement the `onsubmit` method and override
 * `headerTimes`.
 *
 * ### Attrs
 *
 * - `composer`
 * - `originalContent`
 * - `submitLabel`
 * - `placeholder`
 * - `user`
 * - `confirmExit`
 * - `disabled`
 *
 * @abstract
 */
export default class ComposerBody extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.composer = this.attrs.composer;

    /**
     * Whether or not the component is loading.
     *
     * @type {Boolean}
     */
    this.loading = false;

    // Let the composer state know to ask for confirmation under certain
    // circumstances, if the body supports / requires it and has a corresponding
    // confirmation question to ask.
    if (this.attrs.confirmExit) {
      this.composer.preventClosingWhen(() => this.hasChanges(), this.attrs.confirmExit);
    }

    this.composer.fields.content(this.attrs.originalContent || '');
  }

  view() {
    return (
      <ConfirmDocumentUnload when={this.hasChanges.bind(this)}>
        <div className={'ComposerBody ' + (this.attrs.className || '')}>
          {avatar(this.attrs.user, { className: 'ComposerBody-avatar' })}
          <div className="ComposerBody-content">
            <ul className="ComposerBody-header">{listItems(this.headerItems().toArray())}</ul>
            <div className="ComposerBody-editor">
              {TextEditor.component({
                submitLabel: this.attrs.submitLabel,
                placeholder: this.attrs.placeholder,
                disabled: this.loading || this.attrs.disabled,
                composer: this.composer,
                preview: this.jumpToPreview && this.jumpToPreview.bind(this),
                onchange: this.composer.fields.content,
                onsubmit: this.onsubmit.bind(this),
                value: this.composer.fields.content(),
              })}
            </div>
          </div>
          <LoadingIndicator display="unset" containerClassName={classList('ComposerBody-loading', this.loading && 'active')} size="large" />
        </div>
      </ConfirmDocumentUnload>
    );
  }

  /**
   * Check if there is any unsaved data.
   *
   * @return {boolean}
   */
  hasChanges() {
    const content = this.composer.fields.content();

    return content && content !== this.attrs.originalContent;
  }

  /**
   * Build an item list for the composer's header.
   *
   * @return {ItemList<import('mithril').Children>}
   */
  headerItems() {
    return new ItemList();
  }

  /**
   * Handle the submit event of the text editor.
   *
   * @abstract
   */
  onsubmit() {}

  /**
   * Stop loading.
   */
  loaded() {
    this.loading = false;
    m.redraw();
  }
}
