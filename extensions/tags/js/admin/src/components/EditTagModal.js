import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';
import { slug } from 'flarum/utils/string';

import tagLabel from 'tags/helpers/tagLabel';

/**
 * The `EditTagModal` component shows a modal dialog which allows the user
 * to create or edit a tag.
 */
export default class EditTagModal extends Modal {
  constructor(...args) {
    super(...args);

    this.tag = this.props.tag || app.store.createRecord('tags');

    this.name = m.prop(this.tag.name() || '');
    this.slug = m.prop(this.tag.slug() || '');
    this.description = m.prop(this.tag.description() || '');
    this.color = m.prop(this.tag.color() || '');
  }

  className() {
    return 'EditTagModal Modal--small';
  }

  title() {
    return this.name()
      ? tagLabel({
        name: this.name,
        color: this.color
      })
      : 'Create Tag';
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>Name</label>
            <input className="FormControl" placeholder="Name" value={this.name()} oninput={e => {
              this.name(e.target.value);
              this.slug(slug(e.target.value));
            }}/>
          </div>

          <div className="Form-group">
            <label>Slug</label>
            <input className="FormControl" value={this.slug()} oninput={m.withAttr('value', this.slug)}/>
          </div>

          <div className="Form-group">
            <label>Description</label>
            <textarea className="FormControl" value={this.description()} oninput={m.withAttr('value', this.description)}/>
          </div>

          <div className="Form-group">
            <label>Color</label>
            <input className="FormControl" placeholder="#aaaaaa" value={this.color()} oninput={m.withAttr('value', this.color)}/>
          </div>

          <div className="Form-group">
            {Button.component({
              type: 'submit',
              className: 'Button Button--primary EditTagModal-save',
              loading: this._loading,
              children: 'Save Changes'
            })}
            {this.tag.exists ? (
              <button type="button" className="Button EditTagModal-delete" onclick={this.delete.bind(this)}>
                Delete Tag
              </button>
            ) : ''}
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();

    this._loading = true;

    this.tag.save({
      name: this.name(),
      slug: this.slug(),
      description: this.description(),
      color: this.color()
    }).then(
      () => this.hide(),
      () => {
        this._loading = false;
        m.redraw();
      }
    );
  }

  delete() {
    if (confirm('Are you sure you want to delete this tag? The tag\'s discussions will NOT be deleted.')) {
      this.tag.delete().then(() => m.redraw());
      this.hide();
    }
  }
}
