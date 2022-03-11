import sortable from 'sortablejs';

import ExtensionPage from 'flarum/components/ExtensionPage';
import Button from 'flarum/components/Button';
import LoadingIndicator from 'flarum/components/LoadingIndicator';
import withAttr from 'flarum/utils/withAttr';

import EditTagModal from './EditTagModal';
import tagIcon from '../../common/helpers/tagIcon';
import sortTags from '../../common/utils/sortTags';

function tagItem(tag) {
  return (
    <li data-id={tag.id()} style={{ color: tag.color() }}>
      <div className="TagListItem-info">
        {tagIcon(tag)}
        <span className="TagListItem-name">{tag.name()}</span>
        {Button.component({
          className: 'Button Button--link',
          icon: 'fas fa-pencil-alt',
          onclick: () => app.modal.show(EditTagModal, { model: tag })
        })}
      </div>
      {!tag.isChild() && tag.position() !== null ? (
        <ol className="TagListItem-children TagList">
          {sortTags(app.store.all('tags'))
            .filter(child => child.parent() === tag)
            .map(tagItem)}
        </ol>
      ) : ''}
    </li>
  );
}

export default class TagsPage extends ExtensionPage {
  oninit(vnode) {
    super.oninit(vnode);

    // A regular redraw won't work here, because sortable has mucked around
    // with the DOM which will confuse Mithril's diffing algorithm. Instead
    // we force a full reconstruction of the DOM by changing the key, which
    // makes mithril completely re-render the component on redraw.
    this.forcedRefreshKey = 0;

    this.loading = true;

    app.store.find('tags', { include: 'parent' }).then(() => {
      this.loading = false;

      m.redraw();
    });
  }

  content() {
    if (this.loading) {
      return <LoadingIndicator />;
    }

    const minPrimaryTags = this.setting('flarum-tags.min_primary_tags', 0);
    const maxPrimaryTags = this.setting('flarum-tags.max_primary_tags', 0);

    const minSecondaryTags = this.setting('flarum-tags.min_secondary_tags', 0);
    const maxSecondaryTags = this.setting('flarum-tags.max_secondary_tags', 0);

    const tags = sortTags(app.store.all('tags').filter(tag => !tag.parent()));
    
    return (
      <div className="TagsContent">
        <div className="TagsContent-list">
          <div className="container" key={this.forcedRefreshKey} oncreate={this.onListOnCreate.bind(this)}><div className="SettingsGroups">
            <div className="TagGroup">
              <label>{app.translator.trans('flarum-tags.admin.tags.primary_heading')}</label>
              <ol className="TagList TagList--primary">
                {tags
                  .filter(tag => tag.position() !== null && !tag.isChild())
                  .map(tagItem)}
              </ol>
              {Button.component(
                {
                  className: 'Button TagList-button',
                  icon: 'fas fa-plus',
                  onclick: () => app.modal.show(EditTagModal, { primary: true }),
                },
                app.translator.trans('flarum-tags.admin.tags.create_primary_tag_button')
              )}
            </div>

            <div className="TagGroup TagGroup--secondary">
              <label>{app.translator.trans('flarum-tags.admin.tags.secondary_heading')}</label>
              <ul className="TagList">
                {tags
                  .filter(tag => tag.position() === null)
                  .sort((a, b) => a.name().localeCompare(b.name()))
                  .map(tagItem)}
              </ul>
              {Button.component(
                {
                  className: 'Button TagList-button',
                  icon: 'fas fa-plus',
                  onclick: () => app.modal.show(EditTagModal, { primary: false }),
                },
                app.translator.trans('flarum-tags.admin.tags.create_secondary_tag_button')
              )}
            </div>
            <div className="Form">
              <label>{app.translator.trans('flarum-tags.admin.tags.settings_heading')}</label>
              <div className="Form-group">
                <label>{app.translator.trans('flarum-tags.admin.tag_settings.required_primary_heading')}</label>
                <div className="helpText">{app.translator.trans('flarum-tags.admin.tag_settings.required_primary_text')}</div>
                <div className="TagSettings-rangeInput">
                  <input
                    className="FormControl"
                    type="number"
                    min="0"
                    value={minPrimaryTags()}
                    oninput={withAttr('value', this.setMinTags.bind(this, minPrimaryTags, maxPrimaryTags))}
                  />
                  {app.translator.trans('flarum-tags.admin.tag_settings.range_separator_text')}
                  <input className="FormControl" type="number" min={minPrimaryTags()} bidi={maxPrimaryTags} />
                </div>
              </div>
              <div className="Form-group">
                <label>{app.translator.trans('flarum-tags.admin.tag_settings.required_secondary_heading')}</label>
                <div className="helpText">{app.translator.trans('flarum-tags.admin.tag_settings.required_secondary_text')}</div>
                <div className="TagSettings-rangeInput">
                  <input
                    className="FormControl"
                    type="number"
                    min="0"
                    value={minSecondaryTags()}
                    oninput={withAttr('value', this.setMinTags.bind(this, minSecondaryTags, maxSecondaryTags))}
                  />
                  {app.translator.trans('flarum-tags.admin.tag_settings.range_separator_text')}
                  <input className="FormControl" type="number" min={minSecondaryTags()} bidi={maxSecondaryTags} />
                </div>
              </div>
              <div className="Form-group">{this.submitButton()}</div>
            </div>
          </div>
            <div className="TagsContent-footer">
              <p>{app.translator.trans('flarum-tags.admin.tags.about_tags_text')}</p>
            </div>
          </div>
        </div>
      </div>
    );
  }

  onListOnCreate(vnode) {
    this.$('.TagList').get().map(e => {
      sortable.create(e, {
        group: 'tags',
        delay: 50,
        delayOnTouchOnly: true,
        touchStartThreshold: 5,
        animation: 150,
        swapThreshold: 0.65,
        dragClass: 'sortable-dragging',
        ghostClass: 'sortable-placeholder',
        onSort: (e) => this.onSortUpdate(e)
      })
    });
  }

  setMinTags(minTags, maxTags, value) {
    minTags(value);
    maxTags(Math.max(value, maxTags()));
  }

  onSortUpdate(e) {
    // If we've moved a tag from 'primary' to 'secondary', then we'll update
    // its attributes in our local store so that when we redraw the change
    // will be made.
    if (e.from instanceof HTMLOListElement && e.to instanceof HTMLUListElement) {
      app.store.getById('tags', e.item.getAttribute('data-id')).pushData({
        attributes: {
          position: null,
          isChild: false
        },
        relationships: { parent: null }
      });
    }

    // Construct an array of primary tag IDs and their children, in the same
    // order that they have been arranged in.
    const order = this.$('.TagList--primary > li')
      .map(function () {
        return {
          id: $(this).data('id'),
          children: $(this).find('li')
            .map(function () {
              return $(this).data('id');
            }).get()
        };
      }).get();

    // Now that we have an accurate representation of the order which the
    // primary tags are in, we will update the tag attributes in our local
    // store to reflect this order.
    order.forEach((tag, i) => {
      const parent = app.store.getById('tags', tag.id);
      parent.pushData({
        attributes: {
          position: i,
          isChild: false
        },
        relationships: { parent: null }
      });

      tag.children.forEach((child, j) => {
        app.store.getById('tags', child).pushData({
          attributes: {
            position: j,
            isChild: true
          },
          relationships: { parent }
        });
      });
    });

    app.request({
      url: app.forum.attribute('apiUrl') + '/tags/order',
      method: 'POST',
      body: { order }
    });

    this.forcedRefreshKey++;
    m.redraw();
  }
}
