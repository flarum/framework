import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';
import ItemList from 'flarum/utils/ItemList';
import { slug } from 'flarum/utils/string';
import Stream from 'flarum/utils/Stream';

import tagLabel from '../../common/helpers/tagLabel';

/**
 * The `EditTagModal` component shows a modal dialog which allows the user
 * to create or edit a tag.
 */
export default class EditTagModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);

    this.tag = this.attrs.model || app.store.createRecord('tags');

    this.name = Stream(this.tag.name() || '');
    this.slug = Stream(this.tag.slug() || '');
    this.description = Stream(this.tag.description() || '');
    this.color = Stream(this.tag.color() || '');
    this.icon = Stream(this.tag.icon() || '');
    this.isHidden = Stream(this.tag.isHidden() || false);
    this.primary = Stream(this.attrs.primary || false);
  }

  className() {
    return 'EditTagModal Modal--small';
  }

  title() {
    return this.name()
      ? tagLabel({
        name: this.name,
        color: this.color,
        icon: this.icon,
      })
      : app.translator.trans('flarum-tags.admin.edit_tag.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          {this.fields().toArray()}
        </div>
      </div>
    );
  }

  fields() {
    const items = new ItemList();

    items.add('name', <div className="Form-group">
      <label>{app.translator.trans('flarum-tags.admin.edit_tag.name_label')}</label>
      <input className="FormControl" placeholder={app.translator.trans('flarum-tags.admin.edit_tag.name_placeholder')} value={this.name()} oninput={e => {
        this.name(e.target.value);
        this.slug(slug(e.target.value));
      }}/>
    </div>, 50);

    items.add('slug', <div className="Form-group">
      <label>{app.translator.trans('flarum-tags.admin.edit_tag.slug_label')}</label>
      <input className="FormControl" bidi={this.slug}/>
    </div>, 40);

    items.add('description', <div className="Form-group">
      <label>{app.translator.trans('flarum-tags.admin.edit_tag.description_label')}</label>
      <textarea className="FormControl" bidi={this.description}/>
    </div>, 30);

    items.add('color', <div className="Form-group">
      <label>{app.translator.trans('flarum-tags.admin.edit_tag.color_label')}</label>
      <input className="FormControl" placeholder="#aaaaaa" bidi={this.color}/>
    </div>, 20);

    items.add('icon', <div className="Form-group">
      <label>{app.translator.trans('flarum-tags.admin.edit_tag.icon_label')}</label>
      <div className="helpText">
        {app.translator.trans('flarum-tags.admin.edit_tag.icon_text', {a: <a href="https://fontawesome.com/icons?m=free" tabindex="-1"/>})}
      </div>
      <input className="FormControl" placeholder="fas fa-bolt" bidi={this.icon}/>
    </div>, 10);

    items.add('hidden', <div className="Form-group">
      <div>
        <label className="checkbox">
          <input type="checkbox" bidi={this.isHidden}/>
          {app.translator.trans('flarum-tags.admin.edit_tag.hide_label')}
        </label>
      </div>
    </div>, 10);

    items.add('submit', <div className="Form-group">
      {Button.component({
        type: 'submit',
        className: 'Button Button--primary EditTagModal-save',
        loading: this.loading,
      }, app.translator.trans('flarum-tags.admin.edit_tag.submit_button'))}
      {this.tag.exists ? (
        <button type="button" className="Button EditTagModal-delete" onclick={this.delete.bind(this)}>
          {app.translator.trans('flarum-tags.admin.edit_tag.delete_tag_button')}
        </button>
      ) : ''}
    </div>, -10);

    return items;
  }

  submitData() {
    return {
      name: this.name(),
      slug: this.slug(),
      description: this.description(),
      color: this.color(),
      icon: this.icon(),
      isHidden: this.isHidden(),
      primary: this.primary(),
    };
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    // Errors aren't passed to the modal onerror handler here.
    // This is done for better error visibility on smaller screen heights.
    this.tag.save(this.submitData()).then(
      () => this.hide(),
      () => this.loading = false
    );
  }

  delete() {
    if (confirm(app.translator.trans('flarum-tags.admin.edit_tag.delete_tag_confirmation'))) {
      const children = app.store.all('tags').filter(tag => tag.parent() === this.tag);

      this.tag.delete().then(() => {
        children.forEach(tag => tag.pushData({
          attributes: {isChild: false},
          relationships: {parent: null}
        }));
        m.redraw();
      });

      this.hide();
    }
  }
}
