import Component from '../../common/Component';
import LoadingIndicator from '../../common/components/LoadingIndicator';
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
  static initProps(props) {
    super.initProps(props);

    Object.assign(props, props.state.bodyProps);
  }

  init() {
    this.state = this.props.state;

    /**
     * Whether or not the component is loading.
     *
     * @type {Boolean}
     */
    this.loading = false;

    this.state.on('focus', () => {
      this.focus();
    });
    this.state.bodyPreventExit = this.preventExit.bind(this);

    this.state.content(this.props.originalContent);
  }

  view() {
    return (
      <div className={'ComposerBody ' + (this.props.className || '')}>
        {avatar(this.props.user, { className: 'ComposerBody-avatar' })}
        <div className="ComposerBody-content">
          <ul className="ComposerBody-header">{listItems(this.headerItems().toArray())}</ul>
          <div className="ComposerBody-editor">
            {TextEditor.component({
              submitLabel: this.props.submitLabel,
              placeholder: this.props.placeholder,
              disabled: this.loading | this.disabled,
              preview: this.preview.bind(this),
              onchange: this.state.content,
              onsubmit: this.onsubmit.bind(this),
              value: this.state.content(),
            })}
          </div>
        </div>
        {LoadingIndicator.component({ className: 'ComposerBody-loading' + (this.loading ? ' active' : '') })}
      </div>
    );
  }

  /**
   * Draw focus to the text editor.
   */
  focus() {
    this.$(':input:enabled:visible:first').focus();
  }

  /**
   * Check if there is any unsaved data – if there is, return a confirmation
   * message to prompt the user with.
   *
   * @return {String}
   */
  preventExit() {
    const content = this.state.content();

    return content && content !== this.props.originalContent && this.props.confirmExit;
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
   * Get the data to submit to the server when the post is saved.
   *
   * @return {Object}
   */
  data() {
    return {
      content: this.state.content(),
    };
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
