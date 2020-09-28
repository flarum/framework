import Modal from '../../common/components/Modal';
import Button from '../../common/components/Button';
import Badge from '../../common/components/Badge';
import Group from '../../common/models/Group';
import ItemList from '../../common/utils/ItemList';
import Switch from '../../common/components/Switch';
import Stream from '../../common/utils/Stream';

/**
 * The `EditGroupModal` component shows a modal dialog which allows the user
 * to create or edit a group.
 */
export default class EditGroupModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);

    this.group = this.attrs.group || app.store.createRecord('groups');

    this.nameSingular = Stream(this.group.nameSingular() || '');
    this.namePlural = Stream(this.group.namePlural() || '');
    this.icon = Stream(this.group.icon() || '');
    this.color = Stream(this.group.color() || '');
    this.isHidden = Stream(this.group.isHidden() || false);
  }

  className() {
    return 'EditGroupModal Modal--small';
  }

  title() {
    return [
      this.color() || this.icon()
        ? Badge.component({
            icon: this.icon(),
            style: { backgroundColor: this.color() },
          })
        : '',
      ' ',
      this.namePlural() || app.translator.trans('core.admin.edit_group.title'),
    ];
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">{this.fields().toArray()}</div>
      </div>
    );
  }

  fields() {
    const items = new ItemList();

    items.add(
      'name',
      <div className="Form-group">
        <label>{app.translator.trans('core.admin.edit_group.name_label')}</label>
        <div className="EditGroupModal-name-input">
          <input className="FormControl" placeholder={app.translator.trans('core.admin.edit_group.singular_placeholder')} bidi={this.nameSingular} />
          <input className="FormControl" placeholder={app.translator.trans('core.admin.edit_group.plural_placeholder')} bidi={this.namePlural} />
        </div>
      </div>,
      30
    );

    items.add(
      'color',
      <div className="Form-group">
        <label>{app.translator.trans('core.admin.edit_group.color_label')}</label>
        <input className="FormControl" placeholder="#aaaaaa" bidi={this.color} />
      </div>,
      20
    );

    items.add(
      'icon',
      <div className="Form-group">
        <label>{app.translator.trans('core.admin.edit_group.icon_label')}</label>
        <div className="helpText">
          {app.translator.trans('core.admin.edit_group.icon_text', { a: <a href="https://fontawesome.com/icons?m=free" tabindex="-1" /> })}
        </div>
        <input className="FormControl" placeholder="fas fa-bolt" bidi={this.icon} />
      </div>,
      10
    );

    items.add(
      'hidden',
      <div className="Form-group">
        {Switch.component(
          {
            state: !!Number(this.isHidden()),
            onchange: this.isHidden,
          },
          app.translator.trans('core.admin.edit_group.hide_label')
        )}
      </div>,
      10
    );

    items.add(
      'submit',
      <div className="Form-group">
        {Button.component(
          {
            type: 'submit',
            className: 'Button Button--primary EditGroupModal-save',
            loading: this.loading,
          },
          app.translator.trans('core.admin.edit_group.submit_button')
        )}
        {this.group.exists && this.group.id() !== Group.ADMINISTRATOR_ID ? (
          <button type="button" className="Button EditGroupModal-delete" onclick={this.deleteGroup.bind(this)}>
            {app.translator.trans('core.admin.edit_group.delete_button')}
          </button>
        ) : (
          ''
        )}
      </div>,
      -10
    );

    return items;
  }

  submitData() {
    return {
      nameSingular: this.nameSingular(),
      namePlural: this.namePlural(),
      color: this.color(),
      icon: this.icon(),
      isHidden: this.isHidden(),
    };
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    this.group
      .save(this.submitData(), { errorHandler: this.onerror.bind(this) })
      .then(this.hide.bind(this))
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }

  deleteGroup() {
    if (confirm(app.translator.trans('core.admin.edit_group.delete_confirmation'))) {
      this.group.delete().then(() => m.redraw());
      this.hide();
    }
  }
}
