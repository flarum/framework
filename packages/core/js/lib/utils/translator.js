export default class Translator {
  constructor() {
    this.translations = {};
  }

  plural(count) {
    return count == 1 ? 'one' : 'other';
  }

  translate(key, input) {
    var parts = key.split('.');
    var translation = this.translations;

    parts.forEach(function(part) {
      translation = translation && translation[part];
    });

    if (typeof translation === 'object' && typeof input.count !== 'undefined') {
      translation = translation[this.plural(input.count)];
    }

    if (typeof translation === 'string') {
      for (var i in input) {
        translation = translation.replace(new RegExp('{'+i+'}', 'gi'), input[i]);
      }

      return translation;
    } else {
      return key;
    }
  }
}
