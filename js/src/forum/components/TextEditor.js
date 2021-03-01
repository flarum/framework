import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import SuperTextarea from '../../common/utils/SuperTextarea';
import listItems from '../../common/helpers/listItems';
import Button from '../../common/components/Button';

/**
 * The `TextEditor` component displays a textarea with controls, including a
 * submit button.
 *
 * ### Attrs
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

  oncreate(vnode) {
    super.oncreate(vnode);

    const handler = () => {
      this.onsubmit();
      m.redraw();
    };

    this.$('textarea').bind('keydown', 'meta+return', handler);
    this.$('textarea').bind('keydown', 'ctrl+return', handler);

    this.attrs.composer.editor = new SuperTextarea(this.$('textarea')[0]);
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
          oncreate: (vnode) => $(vnode.dom).tooltip(),
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
