import app from 'flarum/forum/app';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import classList from 'flarum/common/utils/classList';
import extractText from 'flarum/common/utils/extractText';

import getSelectableTags from '../utils/getSelectableTags';
import TagSelectionModal, { ITagSelectionModalAttrs } from '../../common/components/TagSelectionModal';

import type Discussion from 'flarum/common/models/Discussion';
import type Tag from '../../common/models/Tag';

export interface TagDiscussionModalAttrs extends ITagSelectionModalAttrs {
  discussion?: Discussion;
}

export default class TagDiscussionModal extends TagSelectionModal<TagDiscussionModalAttrs> {
  static initAttrs(attrs: TagDiscussionModalAttrs) {
    super.initAttrs(attrs);

    const title = attrs.discussion
      ? app.translator.trans('flarum-tags.forum.choose_tags.edit_title', { title: <em>{attrs.discussion.title()}</em> })
      : app.translator.trans('flarum-tags.forum.choose_tags.title');

    attrs.className = classList(attrs.className, 'TagDiscussionModal');
    attrs.title = extractText(title);
    attrs.allowResetting = !!app.forum.attribute('canBypassTagCounts');
    attrs.limits = {
      allowBypassing: attrs.allowResetting,
      max: {
        primary: app.forum.attribute<number>('maxPrimaryTags'),
        secondary: app.forum.attribute<number>('maxSecondaryTags'),
      },
      min: {
        primary: app.forum.attribute<number>('minPrimaryTags'),
        secondary: app.forum.attribute<number>('minSecondaryTags'),
      },
    };
    attrs.requireParentTag = true;
    attrs.selectableTags = () => getSelectableTags(attrs.discussion);
    attrs.selectedTags ??= (attrs.discussion?.tags() as Tag[]) || [];
    attrs.canSelect = (tag) => tag.canStartDiscussion();

    const suppliedOnsubmit = attrs.onsubmit || null;

    // Save changes.
    attrs.onsubmit = function (tags) {
      const discussion = attrs.discussion;

      if (discussion) {
        discussion.save({ relationships: { tags } }).then(() => {
          if (app.current.matches(DiscussionPage)) {
            app.current.get('stream').update();
          }

          m.redraw();
        });
      }

      if (suppliedOnsubmit) suppliedOnsubmit(tags);
    };
  }
}
