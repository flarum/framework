import app from '../../admin/app';
import FormModal, { IFormModalAttrs } from '../../common/components/FormModal';
import Button from '../../common/components/Button';
import Badge from '../../common/components/Badge';
import Group from '../../common/models/Group';
import ItemList from '../../common/utils/ItemList';
import Switch from '../../common/components/Switch';
import Stream from '../../common/utils/Stream';
import Mithril from 'mithril';
import extractText from '../../common/utils/extractText';
import ColorPreviewInput from '../../common/components/ColorPreviewInput';
import IconInput from '../../common/components/IconInput';
import IconImageInput from '../../common/components/IconImageInput';
import IconImageState from '../../common/states/IconImageState';
import Form from '../../common/components/Form';

export interface IEditGroupModalAttrs extends IFormModalAttrs {
  group?: Group;
}

/**
 * The `EditGroupModal` component shows a modal dialog which allows the user
 * to create or edit a group.
 */
export default class EditGroupModal<CustomAttrs extends IEditGroupModalAttrs = IEditGroupModalAttrs> extends FormModal<CustomAttrs> {
  group!: Group;
  nameSingular!: Stream<string>;
  namePlural!: Stream<string>;
  icon!: Stream<string>;
  color!: Stream<string>;
  isHidden!: Stream<boolean>;
  iconImage!: IconImageState;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.group = this.attrs.group || app.store.createRecord('groups');

    this.nameSingular = Stream(this.group.nameSingular() || '');
    this.namePlural = Stream(this.group.namePlural() || '');
    this.icon = Stream(this.group.icon() || '');
    this.color = Stream(this.group.color() || '');
    this.isHidden = Stream(this.group.isHidden() || false);
    this.iconImage = new IconImageState(this.group);
  }

  onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onremove(vnode);

    this.iconImage.dispose();
  }

  className() {
    return 'EditGroupModal Modal--small';
  }

  title() {
    return (
      <>
        {!!(this.color() || this.icon() || this.iconImage.url()) && (
          <Badge icon={this.icon()} image={this.iconImage.url()} imageSrcset={this.iconImage.srcset()} color={this.color()} />
        )}{' '}
        {this.namePlural() || app.translator.trans('core.admin.edit_group.title')}
      </>
    );
  }

  content() {
    return (
      <div className="Modal-body">
        <Form>{this.fields().toArray()}</Form>
      </div>
    );
  }

  fields(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'name',
      <div className="Form-group">
        <label>{app.translator.trans('core.admin.edit_group.name_label')}</label>
        <div className="StackedFormControl EditGroupModal-name-input">
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
          {app.translator.trans('core.admin.edit_group.icon_text', { a: <a href={app.refs.fontawesome} tabindex="-1" /> })}
        </div>
        <IconInput placeholder="fas fa-bolt" bidi={this.icon} />
      </div>,
      10
    );

    items.add(
      'iconImage',
      <div className="Form-group">
        <label>{app.translator.trans('core.admin.edit_group.icon_image_label')}</label>
        <div className="helpText">{app.translator.trans('core.admin.edit_group.icon_image_text')}</div>
        <IconImageInput state={this.iconImage} />
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
      <div className="Form-group Form-controls">
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

    const errorHandler = this.onerror.bind(this);

    this.group
      .save(this.submitData(), { errorHandler })
      .then((group) => {
        // A new group's save resolves to another instance. Keep it, so that
        // saving again after a failed upload doesn't create a second group.
        this.group = group;

        return this.iconImage.save(group, { errorHandler });
      })
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
