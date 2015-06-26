export class Item {
  constructor(content, position) {
    this.content = content;
    this.position = position;
  }
}

export default class ItemList {
  add(key, content, position) {
    this[key] = new Item(content, position);
  }

  merge(items) {
    for (var i in items) {
      if (items.hasOwnProperty(i) && items[i] instanceof Item) {
        this[i] = items[i];
      }
    }
  }

  toArray() {
    var items = [];
    for (var i in this) {
      if (this.hasOwnProperty(i) && this[i] instanceof Item) {
        this[i].content.itemName = i;
        items.push(this[i]);
      }
    }

    var array = [];

    var addItems = function(method, position) {
      items = items.filter(function(item) {
        if ((position && item.position && item.position[position]) || (!position && !item.position)) {
          array[method](item);
        } else {
          return true;
        }
      });
    };
    addItems('unshift', 'first');
    addItems('push', false);
    addItems('push', 'last');

    items.forEach(item => {
      var key = item.position.before || item.position.after;
      var type = item.position.before ? 'before' : 'after';
      // TODO: Allow both before and after to be specified, and multiple keys to
      // be specified for each.
      // e.g. {before: ['foo', 'bar'], after: ['qux', 'qaz']}
      // This way extensions can make sure they are positioned where
      // they want to be relative to other extensions.
      // Alternatively, it might be better to just have a numbered priority
      // system, so extensions don't have to make awkward references to each other.
      if (key) {
        var index = array.indexOf(this[key]);
        if (index === -1) {
          array.push(item);
        } else {
          array.splice(index + (type === 'after' ? 1 : 0), 0, item);
        }
      }
    });

    array = array.map(item => item.content);

    return array;
  }
}

