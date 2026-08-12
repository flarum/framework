import app from 'flarum/admin/app';
import FormModal, { IFormModalAttrs } from 'flarum/common/components/FormModal';
import Button from 'flarum/common/components/Button';
import ColorPreviewInput from 'flarum/common/components/ColorPreviewInput';
import Select from 'flarum/common/components/Select';
import ItemList from 'flarum/common/utils/ItemList';
import { slug } from 'flarum/common/utils/string';
import Stream from 'flarum/common/utils/Stream';
import extractText from 'flarum/common/utils/extractText';
import Form from 'flarum/common/components/Form';
import type Mithril from 'mithril';

import tagLabel from '../../common/helpers/tagLabel';
import type Tag from '../../common/models/Tag';

export interface EditTagModalAttrs extends IFormModalAttrs {
  primary?: boolean;
  model?: Tag;
}

/**
 * The `EditTagModal` component shows a modal dialog which allows the user
 * to create or edit a tag.
 */
export default class EditTagModal extends FormModal<EditTagModalAttrs> {
  tag!: Tag;

  name!: Stream<string>;
  slug!: Stream<string>;
  description!: Stream<string>;
  color!: Stream<string>;
  icon!: Stream<string>;
  isHidden!: Stream<boolean>;
  isPrimary!: Stream<boolean>;
  defaultSort!: Stream<string>;

  oninit(vnode: Mithril.Vnode<EditTagModalAttrs, this>) {
    super.oninit(vnode);

    this.tag = this.attrs.model || app.store.createRecord('tags');

    this.name = Stream(this.tag.name() || '');
    this.slug = Stream(this.tag.storedSlug() || '');
    this.description = Stream(this.tag.description() || '');
    this.color = Stream(this.tag.color() || '');
    this.icon = Stream(this.tag.icon() || '');
    this.isHidden = Stream(this.tag.isHidden() || false);
    this.isPrimary = Stream(this.attrs.primary || false);
    this.defaultSort = Stream(this.tag.defaultSort() || '');
  }

  className() {
    return 'EditTagModal Modal--small';
  }

  title() {
    return this.name()
      ? tagLabel(app.store.createRecord('tags', { attributes: this.submitData() }))
      : app.translator.trans('flarum-tags.admin.edit_tag.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <Form>{this.fields().toArray()}</Form>
      </div>
    );
  }

  fields() {
    const items = new ItemList();

    items.add(
      'name',
      <div className="Form-group">
        <label>{app.translator.trans('flarum-tags.admin.edit_tag.name_label')}</label>
        <input
          className="FormControl"
          placeholder={app.translator.trans('flarum-tags.admin.edit_tag.name_placeholder')}
          value={this.name()}
          oninput={(e: InputEvent) => {
            const target = e.target as HTMLInputElement;
            this.name(target.value);
            this.slug(slug(target.value));
          }}
        />
      </div>,
      50
    );

    items.add(
      'slug',
      <div className="Form-group">
        <label>{app.translator.trans('flarum-tags.admin.edit_tag.slug_label')}</label>
        <input className="FormControl" bidi={this.slug} />
      </div>,
      40
    );

    items.add(
      'description',
      <div className="Form-group">
        <label>{app.translator.trans('flarum-tags.admin.edit_tag.description_label')}</label>
        <textarea className="FormControl" bidi={this.description} />
      </div>,
      30
    );

    items.add(
      'color',
      <div className="Form-group">
        <label>{app.translator.trans('flarum-tags.admin.edit_tag.color_label')}</label>
        <ColorPreviewInput className="FormControl" placeholder="#aaaaaa" bidi={this.color} />
      </div>,
      20
    );

    items.add(
      'icon',
      <div className="Form-group">
        <label>{app.translator.trans('flarum-tags.admin.edit_tag.icon_label')}</label>
        <div className="helpText">
          {app.translator.trans('flarum-tags.admin.edit_tag.icon_text', { a: <a href={app.refs.fontawesome} tabindex="-1" /> })}
        </div>
        <input className="FormControl" placeholder="fas fa-bolt" bidi={this.icon} />
      </div>,
      10
    );

    items.add(
      'hidden',
      <div className="Form-group">
        <div>
          <label className="checkbox">
            <input type="checkbox" bidi={this.isHidden} />
            {app.translator.trans('flarum-tags.admin.edit_tag.hide_label')}
          </label>
        </div>
      </div>,
      10
    );

    items.add('defaultSort', this.defaultSortField(), 5);

    items.add(
      'submit',
      <div className="Form-group Form-controls">
        <Button type="submit" className="Button Button--primary EditTagModal-save" loading={this.loading}>
          {app.translator.trans('flarum-tags.admin.edit_tag.submit_button')}
        </Button>

        {this.tag.exists && (
          <button type="button" className="Button EditTagModal-delete" onclick={this.delete.bind(this)}>
            {app.translator.trans('flarum-tags.admin.edit_tag.delete_tag_button')}
          </button>
        )}
      </div>,
      -10
    );

    return items;
  }

  /**
   * The sorts a discussion list can be ordered by, as the forum offers them.
   *
   * Read from the payload rather than listed here, so that a sort added by an
   * extension can be chosen as a tag's default without that extension knowing
   * anything about tags.
   */
  sortOptions(): Record<string, string> {
    const sorts = app.data.sortMaps?.discussions ?? {};

    const options: Record<string, string> = {
      '': extractText(app.translator.trans('flarum-tags.admin.edit_tag.default_sort_none')),
    };

    Object.keys(sorts).forEach((key) => {
      options[key] = extractText(app.translator.trans(`core.lib.index_sort.${key}_button`));
    });

    // A sort saved earlier whose extension has since gone is kept in the list,
    // so that it is not silently replaced by whatever happens to sit at the top
    // of the dropdown when the tag is next edited.
    const saved = this.defaultSort();

    if (saved && !(saved in options)) {
      options[saved] = extractText(app.translator.trans('flarum-tags.admin.edit_tag.default_sort_unavailable', { sort: saved }));
    }

    return options;
  }

  defaultSortField() {
    return (
      <div className="Form-group">
        <label>{app.translator.trans('flarum-tags.admin.edit_tag.default_sort_label')}</label>
        <div className="helpText">{app.translator.trans('flarum-tags.admin.edit_tag.default_sort_text')}</div>
        <Select value={this.defaultSort() ?? ''} options={this.sortOptions()} onchange={this.defaultSort} />
      </div>
    );
  }

  submitData() {
    return {
      name: this.name(),
      slug: this.slug(),
      description: this.description(),
      color: this.color(),
      icon: this.icon(),
      isHidden: this.isHidden(),
      isPrimary: this.isPrimary(),
      // An empty selection means "no preference", which the API stores as null.
      defaultSort: this.defaultSort() || null,
    };
  }

  onsubmit(e: SubmitEvent) {
    e.preventDefault();

    this.loading = true;

    // Errors aren't passed to the modal onerror handler here.
    // This is done for better error visibility on smaller screen heights.
    this.tag.save(this.submitData()).then(
      () => this.hide(),
      () => (this.loading = false)
    );
  }

  delete() {
    if (confirm(extractText(app.translator.trans('flarum-tags.admin.edit_tag.delete_tag_confirmation')))) {
      const children = app.store.all<Tag>('tags').filter((tag) => tag.parent() === this.tag);

      this.tag.delete().then(() => {
        children.forEach((tag) =>
          tag.pushData({
            attributes: { isChild: false },
          })
        );
        m.redraw();
      });

      this.hide();
    }
  }
}
