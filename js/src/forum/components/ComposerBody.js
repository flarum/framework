import Component from '../../common/Component';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import ConfirmDocumentUnload from '../../common/components/ConfirmDocumentUnload';
import TextEditor from './TextEditor';
import avatar from '../../common/helpers/avatar';
import listItems from '../../common/helpers/listItems';
import ItemList from '../../common/utils/ItemList';

/**
 * The `ComposerBody` component handles the body, or the content, of the
 * composer. Subclasses should implement the `onsubmit` method and override
 * `headerTimes`.
 *
 * ### Props
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
  init() {
    this.composer = this.props.composer;

    /**
     * Whether or not the component is loading.
     *
     * @type {Boolean}
     */
    this.loading = false;

    // Let the composer state know to ask for confirmation under certain
    // circumstances, if the body supports / requires it and has a corresponding
    // confirmation question to ask.
    if (this.props.confirmExit) {
      this.composer.preventClosingWhen(() => this.preventExit(), this.props.confirmExit);
    }

    this.composer.content(this.props.originalContent || '');

    /**
     * @deprecated BC layer, remove in Beta 15.
     */
    this.content = this.composer.fields.content;
    this.editor = this.composer;
  }

  view() {
    return (
      <ConfirmDocumentUnload when={this.preventExit.bind(this)}>
        <div className={'ComposerBody ' + (this.props.className || '')}>
          {avatar(this.props.user, { className: 'ComposerBody-avatar' })}
          <div className="ComposerBody-content">
            <ul className="ComposerBody-header">{listItems(this.headerItems().toArray())}</ul>
            <div className="ComposerBody-editor">
              {TextEditor.component({
                submitLabel: this.props.submitLabel,
                placeholder: this.props.placeholder,
                disabled: this.loading || this.props.disabled,
                composer: this.composer,
                preview: this.preview.bind(this),
                onchange: this.composer.content,
                onsubmit: this.onsubmit.bind(this),
                value: this.composer.content(),
              })}
            </div>
          </div>
          {LoadingIndicator.component({ className: 'ComposerBody-loading' + (this.loading ? ' active' : '') })}
        </div>
      </ConfirmDocumentUnload>
    );
  }

  /**
   * Check if there is any unsaved data – if there is, return a confirmation
   * message to prompt the user with.
   *
   * @return {String}
   */
  preventExit() {
    const content = this.composer.content();

    return content && content !== this.props.originalContent;
  }

  /**
   * Handle preview for the text editor.
   */
  preview(e) {}

  /**
   * Build an item list for the composer's header.
   *
   * @return {ItemList}
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
