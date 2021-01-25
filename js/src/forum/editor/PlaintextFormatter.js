import { DOMParser, DOMSerializer } from 'prosemirror-model';

/**
 * Core's editor is intended to be unopinionated and functionally
 * identical to a simple textarea. For this reason, the default schema
 * consists of the minimum: plaintext grouped by paragraphs.
 *
 * Accordingly, the plaintext formatter converts plaintext to/from
 * simple, line-break separated lines.
 */
export default class PlaintextFormatter {
  constructor(schema) {
    this.schema = schema;
  }

  parse(text) {
    let parseContainer = document.createElement('div');

    text.split('\n').forEach((line) => {
      const paragraphDom = document.createElement('p');
      paragraphDom.innerText = line;
      parseContainer.appendChild(paragraphDom);
    });

    return DOMParser.fromSchema(this.schema).parse(parseContainer);
  }

  serialize(doc) {
    const serializeContainer = DOMSerializer.fromSchema(this.schema).serializeFragment(doc);

    const lines = [];

    serializeContainer.childNodes.forEach((paragraphDom) => {
      lines.push(paragraphDom.innerText);
    });

    return lines.join('\n');
  }
}
