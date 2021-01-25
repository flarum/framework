import { Plugin } from 'prosemirror-state';
import { Decoration, DecorationSet } from 'prosemirror-view';

export default function placeholderPlugin(text) {
  return new Plugin({
    props: {
      decorations: (state) => {
        const decorations = [];

        const decorate = (node, pos) => {
          if (node.type.isBlock && node.childCount === 0) {
            decorations.push(
              Decoration.node(pos, pos + node.nodeSize, {
                class: 'placeholder',
                'data-before': text,
              })
            );
          }
        };

        state.doc.descendants(decorate);

        return DecorationSet.create(state.doc, decorations);
      },
    },
  });
}
