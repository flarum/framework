import app from 'flarum/forum/app';
import extractText from 'flarum/common/utils/extractText';
import isDark from 'flarum/common/utils/isDark';

export function filterTagMentions(tag) {
  if (app.initializers.has('flarum-mentions')) {
    const tagModel = app.store.getById('tags', tag.getAttribute('id'));

    if (tagModel) {
      tag.setAttribute('tagname', extractText(tagModel.name()));
      tag.setAttribute('icon', tagModel.icon());
      tag.setAttribute('color', tagModel.color());
      tag.setAttribute('slug', tagModel.slug());
      tag.setAttribute('class', isDark(tagModel.color()) ? 'TagMention--light' : 'TagMention--dark');

      return true;
    }
  }

  tag.invalidate();
}
