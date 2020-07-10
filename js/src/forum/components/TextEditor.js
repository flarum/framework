import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';
import Button from '../../common/components/Button';

/**
 * The `TextEditor` component displays a textarea with controls, including a
 * submit button.
 *
 * ### Props
 *
 * - `submitLabel`
 * - `value`
 * - `placeholder`
 * - `disabled`
 */
export default class TextEditor extends Component {
  init() {
    /**
     * The value of the textarea.
     *
     * @type {String}
     */
    this.state = this.props.state;
    this.value = this.props.value || '';
  }

  view() {
    return (
      <div className="TextEditor">
        <textarea
          className="FormControl Composer-flexible"
          config={this.configTextarea.bind(this)}
          oninput={m.withAttr('value', this.oninput.bind(this))}
          placeholder={this.props.placeholder || ''}
          disabled={!!this.props.disabled}
          value={this.value}
        />

        <ul className="TextEditor-controls Composer-footer">
          {listItems(this.controlItems().toArray())}
          <li className="TextEditor-toolbar">{this.toolbarItems().toArray()}</li>
        </ul>
      </div>
    );
  }

  /**
   * Configure the textarea element.
   *
   * @param {DOMElement} element
   * @param {Boolean} isInitialized
   */
  configTextarea(element, isInitialized) {
    if (isInitialized) return;

    const handler = () => {
      this.onsubmit();
      m.redraw();
    };

    $(element).bind('keydown', 'meta+return', handler);
    $(element).bind('keydown', 'ctrl+return', handler);

    this.state.$texteditor = $(element);
  }

  /**
   * Build an item list for the text editor controls.
   *
   * @return {ItemList}
   */
  controlItems() {
    const items = new ItemList();

    items.add(
      'submit',
      Button.component({
        children: this.props.submitLabel,
        icon: 'fas fa-paper-plane',
        className: 'Button Button--primary',
        itemClassName: 'App-primaryControl',
        onclick: this.onsubmit.bind(this),
      })
    );

    if (this.props.preview) {
      items.add(
        'preview',
        Button.component({
          icon: 'far fa-eye',
          className: 'Button Button--icon',
          onclick: this.props.preview,
          title: app.translator.trans('core.forum.composer.preview_tooltip'),
          config: (elm) => $(elm).tooltip(),
        })
      );
    }

    return items;
  }

  /**
   * Build an item list for the toolbar controls.
   *
   * @return {ItemList}
   */
  toolbarItems() {
    return new ItemList();
  }

  /**
   * Handle input into the textarea.
   *
   * @param {String} value
   */
  oninput(value) {
    this.value = value;

    this.props.onchange(this.value);

    m.redraw.strategy('none');
  }

  /**
   * Handle the submit button being clicked.
   */
  onsubmit() {
    this.props.onsubmit(this.value);
  }
}
