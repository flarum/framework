import { extend } from 'flarum/extension-utils';
import IndexPage from 'flarum/components/index-page';
import DiscussionList from 'flarum/components/discussion-list';

import TagHero from 'flarum-tags/components/tag-hero';

export default function() {
  IndexPage.prototype.currentTag = function() {
    var slug = this.params().tags;
    if (slug) {
      return app.store.getBy('tags', 'slug', slug);
    }
  };

  var originalThemeColor = $('meta[name=theme-color]').attr('content');

  // If currently viewing a tag, insert a tag hero at the top of the
  // view and set the theme color accordingly.
  extend(IndexPage.prototype, 'view', function(view) {
    var tag = this.currentTag();
    if (tag) {
      view.children[0] = TagHero.component({tag});
    }
    $('meta[name=theme-color]').attr('content', tag ? tag.color() : originalThemeColor);
  });

  // If currently viewing a tag, restyle the 'new discussion' button to use
  // the tag's color.
  extend(IndexPage.prototype, 'sidebarItems', function(items) {
    var tag = this.currentTag();
    if (tag) {
      var color = tag.color();
      if (color) {
        items.newDiscussion.content.props.style = 'background-color: '+color;
      }
    }
  });

  // Add a parameter for the IndexPage to pass on to the DiscussionList that
  // will let us filter discussions by tag.
  extend(IndexPage.prototype, 'params', function(params) {
    params.tags = m.route.param('tags');
  });

  // Translate that parameter into a gambit appended to the search query.
  extend(DiscussionList.prototype, 'params', function(params) {
    params.include.push('tags');
    if (params.tags) {
      params.q = (params.q || '')+' tag:'+params.tags;
      delete params.tags;
    }
  });
};
