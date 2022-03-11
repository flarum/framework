export default function getSelectableTags(discussion) {
  let tags = app.store.all('tags');

  if (discussion) {
    tags = tags.filter(tag => tag.canAddToDiscussion() || discussion.tags().indexOf(tag) !== -1);
  } else {
    tags = tags.filter(tag => tag.canStartDiscussion());
  }

  return tags;
}
