import Component from 'flarum/component';
import ItemList from 'flarum/utils/item-list';
import listItems from 'flarum/helpers/list-items';
import ActionButton from 'flarum/components/action-button';

/**
  A text editor. Contains a textarea and an item list of `controls`, including
  a submit button.
 */
export default class TextEditor extends Component {
  constructor(props) {
    props.submitLabel = props.submitLabel || 'Submit';

    super(props);

    this.value = m.prop(this.props.value || '');
  }

  view() {
    return m('div.text-editor', {config: this.element}, [
      m('textarea.form-control.flexible-height', {
        config: this.configTextarea.bind(this),
        onkeyup: m.withAttr('value', this.onkeyup.bind(this)),
        placeholder: this.props.placeholder || '',
        disabled: !!this.props.disabled,
        value: this.props.value || ''
      }),
      m('ul.text-editor-controls.fade', listItems(this.controlItems().toArray()))
    ]);
  }

  configTextarea(element, isInitialized) {
    if (isInitialized) { return; }

    $(element).bind('keydown', 'meta+return', this.onsubmit.bind(this));
  }

  controlItems() {
    var items = new ItemList();

    items.add('submit',
      ActionButton.component({
        label: this.props.submitLabel,
        icon: 'check',
        className: 'btn btn-primary',
        wrapperClass: 'primary-control',
        onclick: this.onsubmit.bind(this)
      })
    );

    return items;
  }

  onkeyup(value) {
    this.value(value);
    this.props.onchange(this.value());
    this.$('.text-editor-controls').toggleClass('in', !!value);

    m.redraw.strategy('none');
  }

  onsubmit() {
    this.props.onsubmit(this.value());
  }
}
