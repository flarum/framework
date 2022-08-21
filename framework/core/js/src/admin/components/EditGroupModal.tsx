import app from '../../admin/app';
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import Button from '../../common/components/Button';
import Badge from '../../common/components/Badge';
import Group from '../../common/models/Group';
import ItemList from '../../common/utils/ItemList';
import Switch from '../../common/components/Switch';
import Stream from '../../common/utils/Stream';
import Mithril from 'mithril';
import extractText from '../../common/utils/extractText';
import ColorPreviewInput from '../../common/components/ColorPreviewInput';

export interface IEditGroupModalAttrs extends IInternalModalAttrs {
  group?: Group;
}

/**
 * The `EditGroupModal` component shows a modal dialog which allows the user
 * to create or edit a group.
 */
export default class EditGroupModal<CustomAttrs extends IEditGroupModalAttrs = IEditGroupModalAttrs> extends Modal<CustomAttrs> {
  group!: Group;
  nameSingular!: Stream<string>;
  namePlural!: Stream<string>;
  icon!: Stream<string>;
  color!: Stream<string>;
  isHidden!: Stream<boolean>;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
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
    return (
      <>
        {!!(this.color() || this.icon()) && <Badge icon={this.icon()} color={this.color()} />}{' '}
        {this.namePlural() || app.translator.trans('core.admin.edit_group.title')}
      </>
    );
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">{this.fields().toArray()}</div>
      </div>
    );
  }

  fields(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

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
        <ColorPreviewInput placeholder="#aaaaaa" bidi={this.color} />
      </div>,
      20
    );

    items.add(
      'icon',
      <div className="Form-group">
        <label>{app.translator.trans('core.admin.edit_group.icon_label')}</label>
        <div className="helpText">
          {app.translator.trans('core.admin.edit_group.icon_text', { a: <a href="https://fontawesome.com/v5/search?m=free" tabindex="-1" /> })}
        </div>
        <input className="FormControl" placeholder="fas fa-bolt" bidi={this.icon} />
      </div>,
      10
    );

    items.add(
      'hidden',
      <div className="Form-group">
        <Switch state={this.isHidden()} onchange={this.isHidden}>
          {app.translator.trans('core.admin.edit_group.hide_label')}
        </Switch>
      </div>,
      10
    );

    items.add(
      'submit',
      <div className="Form-group">
        <Button type="submit" className="Button Button--primary EditGroupModal-save" loading={this.loading}>
          {app.translator.trans('core.admin.edit_group.submit_button')}
        </Button>

        {this.group.exists && this.group.id() !== Group.ADMINISTRATOR_ID && (
          <button type="button" className="Button EditGroupModal-delete" onclick={this.deleteGroup.bind(this)}>
            {app.translator.trans('core.admin.edit_group.delete_button')}
          </button>
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

  onsubmit(e: SubmitEvent) {
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
    if (confirm(extractText(app.translator.trans('core.admin.edit_group.delete_confirmation')))) {
      this.group.delete().then(() => m.redraw());
      this.hide();
    }
  }
}
