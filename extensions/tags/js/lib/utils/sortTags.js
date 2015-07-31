export default function sortTags(tags) {
  return tags.slice(0).sort((a, b) => {
    const aPos = a.position();
    const bPos = b.position();

    const aParent = a.parent();
    const bParent = b.parent();

    if (aPos === null && bPos === null) {
      return b.discussionsCount() - a.discussionsCount();
    } else if (bPos === null) {
      return -1;
    } else if (aPos === null) {
      return 1;
    } else if (aParent === bParent) {
      return aPos - bPos;
    } else if (aParent) {
      return aParent === b ? 1 : aParent.position() - bPos;
    } else if (bParent) {
      return bParent === a ? -1 : aPos - bParent.position();
    }

    return 0;
  });
}
