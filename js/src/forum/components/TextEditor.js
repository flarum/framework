import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import SuperTextarea from '../../common/utils/SuperTextarea';
import listItems from '../../common/helpers/listItems';
import Button from '../../common/components/Button';

/**
 * The `TextEditor` component displays a textarea with controls, including a
 * submit button.
 *
 * ### Props
 *
 * - `composer`
 * - `submitLabel`
 * - `value`
 * - `placeholder`
 * - `disabled`
 * - `preview`
 */
export default class TextEditor extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    /**
     * The value of the textarea.
     *
     * @type {String}
     */
    this.value = this.attrs.value || '';
  }

  view() {
    return (
      <div className="TextEditor">
        <textarea
          className="FormControl Composer-flexible"
          config={this.configTextarea.bind(this)}
          oninput={(e) => {
            this.oninput(e.target.value, e);
          }}
          placeholder={this.attrs.placeholder || ''}
          disabled={!!this.attrs.disabled}
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
   * @param {HTMLTextAreaElement} element
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

    this.attrs.composer.editor = new SuperTextarea(element);
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
      Button.component(
        {
          icon: 'fas fa-paper-plane',
          className: 'Button Button--primary',
          itemClassName: 'App-primaryControl',
          onclick: this.onsubmit.bind(this),
        },
        this.attrs.submitLabel
      )
    );

    if (this.attrs.preview) {
      items.add(
        'preview',
        Button.component({
          icon: 'far fa-eye',
          className: 'Button Button--icon',
          onclick: this.attrs.preview,
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
  oninput(value, e) {
    this.value = value;

    this.attrs.onchange(this.value);

    e.redraw = false;
  }

  /**
   * Handle the submit button being clicked.
   */
  onsubmit() {
    this.attrs.onsubmit(this.value);
  }
}
