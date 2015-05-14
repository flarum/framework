export default function punctuate(items) {
  var newItems = [];

  items.forEach((item, i) => {
    newItems.push(item);

    if (i <= items.length - 2) {
      newItems.push((items.length > 2 ? ', ' : '')+(i === items.length - 2 ? ' and ' : ''));
    }
  });

  return newItems;
};
