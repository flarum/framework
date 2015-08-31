import Component from 'flarum/Component';
import Button from 'flarum/components/Button';

import EditTagModal from 'tags/components/EditTagModal';
import TagSettingsModal from 'tags/components/TagSettingsModal';
import tagIcon from 'tags/helpers/tagIcon';
import sortTags from 'tags/utils/sortTags';

function tagItem(tag) {
  return (
    <li data-id={tag.id()} style={{color: tag.color()}}>
      <div className="TagListItem-info">
        {tagIcon(tag)}
        <span className="TagListItem-name">{tag.name()}</span>
        {Button.component({
          className: 'Button Button--link',
          icon: 'pencil',
          onclick: () => app.modal.show(new EditTagModal({tag}))
        })}
      </div>
      {!tag.isChild() && tag.position() !== null ? (
        <ol className="TagListItem-children">
          {sortTags(app.store.all('tags'))
            .filter(child => child.parent() === tag)
            .map(tagItem)}
        </ol>
      ) : ''}
    </li>
  );
}

export default class TagsPage extends Component {
  view() {
    return (
      <div className="TagsPage">
        <div className="TagsPage-header">
          <div className="container">
            <p>
              Tags are used to categorize discussions. Primary tags are like traditional forum categories: They can be arranged in a two-level hierarchy. Secondary tags do not have hierarchy or order, and are useful for micro-categorization.
            </p>
            {Button.component({
              className: 'Button Button--primary',
              icon: 'plus',
              children: 'Create Tag',
              onclick: () => app.modal.show(new EditTagModal())
            })}
            {Button.component({
              className: 'Button',
              children: 'Settings',
              onclick: () => app.modal.show(new TagSettingsModal())
            })}
          </div>
        </div>
        <div className="TagsPage-list">
          <div className="container">
            <div className="TagGroup">
              <label>Primary Tags</label>
              <ol className="TagList TagList--primary">
                {sortTags(app.store.all('tags'))
                  .filter(tag => tag.position() !== null && !tag.isChild())
                  .map(tagItem)}
              </ol>
            </div>

            <div className="TagGroup">
              <label>Secondary Tags</label>
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

  config() {
    this.$('ol, ul')
      .sortable({connectWith: 'primary'})
      .on('sortupdate', (e, ui) => {
        // If we've moved a tag from 'primary' to 'secondary', then we'll update
        // its attributes in our local store so that when we redraw the change
        // will be made.
        if (ui.startparent.is('ol') && ui.endparent.is('ul')) {
          app.store.getById('tags', ui.item.data('id')).pushData({
            attributes: {
              position: null,
              isChild: false
            },
            relationships: {parent: null}
          });
        }

        // Construct an array of primary tag IDs and their children, in the same
        // order that they have been arranged in.
        const order = this.$('.TagList--primary > li')
          .map(function() {
            return {
              id: $(this).data('id'),
              children: $(this).find('li')
                .map(function() {
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
            relationships: {parent: null}
          });

          tag.children.forEach((child, j) => {
            app.store.getById('tags', child).pushData({
              attributes: {
                position: j,
                isChild: true
              },
              relationships: {parent}
            });
          });
        });

        app.request({
          url: app.forum.attribute('apiUrl') + '/tags/order',
          method: 'POST',
          data: {order}
        });

        // A diff redraw won't work here, because sortable has mucked around
        // with the DOM which will confuse Mithril's diffing algorithm. Instead
        // we force a full reconstruction of the DOM.
        m.redraw.strategy('all');
        m.redraw();
      });
  }
}
