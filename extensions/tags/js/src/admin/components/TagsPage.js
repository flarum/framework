import sortable from 'sortablejs';

import Page from 'flarum/components/Page';
import Button from 'flarum/components/Button';

import EditTagModal from './EditTagModal';
import TagSettingsModal from './TagSettingsModal';
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

export default class TagsPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);

    // A regular redraw won't work here, because sortable has mucked around
    // with the DOM which will confuse Mithril's diffing algorithm. Instead
    // we force a full reconstruction of the DOM by changing the key, which
    // makes mithril completely re-render the component on redraw.
    this.forcedRefreshKey = 0;
  }

  view() {
    return (
      <div className="TagsPage">
        <div className="TagsPage-header">
          <div className="container">
            <p>
              {app.translator.trans('flarum-tags.admin.tags.about_tags_text')}
            </p>
            {Button.component({
              className: 'Button Button--primary',
              icon: 'fas fa-plus',
              onclick: () => app.modal.show(EditTagModal)
            }, app.translator.trans('flarum-tags.admin.tags.create_tag_button'))}
            {Button.component({
              className: 'Button',
              onclick: () => app.modal.show(TagSettingsModal)
            }, app.translator.trans('flarum-tags.admin.tags.settings_button'))}
          </div>
        </div>
        <div className="TagsPage-list">
          <div className="container" key={this.forcedRefreshKey} oncreate={this.onListOnCreate.bind(this)}>
            <div className="TagGroup">
              <label>{app.translator.trans('flarum-tags.admin.tags.primary_heading')}</label>
              <ol className="TagList TagList--primary">
                {sortTags(app.store.all('tags'))
                  .filter(tag => tag.position() !== null && !tag.isChild())
                  .map(tagItem)}
              </ol>
            </div>

            <div className="TagGroup">
              <label>{app.translator.trans('flarum-tags.admin.tags.secondary_heading')}</label>
              <ul className="TagList">
                {app.store.all('tags')
                  .filter(tag => tag.position() === null)
                  .sort((a, b) => a.name().localeCompare(b.name()))
                  .map(tagItem)}
              </ul>
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
        animation: 150,
        swapThreshold: 0.65,
        dragClass: 'sortable-dragging',
        ghostClass: 'sortable-placeholder',
        onSort: (e) => this.onSortUpdate(e)
      })
    });
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
