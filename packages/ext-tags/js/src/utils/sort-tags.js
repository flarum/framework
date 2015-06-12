export default function sortTags(tags) {
  return tags.slice(0).sort((a, b) => {
    var aPos = a.position();
    var bPos = b.position();

    var aParent = a.parent();
    var bParent = b.parent();

    if (aPos === null && bPos === null) {
      return b.discussionsCount() - a.discussionsCount();
    } else if (bPos === null) {
      return -1;
    } else if (aPos === null) {
      return 1;
    } else if (aParent === bParent) {
      return aPos - bPos;
    } else if (aParent) {
      return aParent.position() - bPos;
    } else if (bParent) {
      return aPos - bParent.position();
    }

    return 0;
  });
};
