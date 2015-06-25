import Separator from 'flarum/components/separator';

function isSeparator(item) {
  return item && item.component === Separator;
}

export default function listItems(array, noWrap) {
  // Remove duplicate/unnecessary separators
  var prevItem;
  var newArray = [];
  array.forEach(function(item, i) {
    if ((!prevItem || isSeparator(prevItem) || i === array.length - 1) && isSeparator(item)) {

    } else {
      prevItem = item;
      newArray.push(item);
    }
  });

  return newArray.map(item => [(noWrap && !isSeparator(item)) ? item : m('li', {className: 'item-'+item.itemName+' '+(item.wrapperClass || (item.props && item.props.wrapperClass) || (item.component && item.component.wrapperClass) || '')}, item), ' ']);
};
