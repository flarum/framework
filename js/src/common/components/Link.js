import Component from '../Component';
import extract from '../utils/extract';

/**
 * The link component enables both internal and external links.
 * It will return a regular HTML link for any links to external sites,
 * and it will use Mithril's m.route.Link for any internal links.
 *
 * Links will default to internal; the 'external' attr must be set to
 * `true` for the link to be external.
 */
export default class Link extends Component {
  view(vnode) {
    let { options = {}, ...attrs } = vnode.attrs;

    attrs.href = attrs.href || '';

    // For some reason, m.route.Link does not like vnode.text, so if present, we
    // need to convert it to text vnodes and store it in children.
    const children = vnode.children || { tag: '#', children: vnode.text };

    if (attrs.external) {
      return <a {...attrs}>{children}</a>;
    }

    // If the href URL of the link is the same as the current page path
    // we will not add a new entry to the browser history.
    // This allows us to still refresh the Page component
    // without adding endless history entries.
    if (attrs.href === m.route.get()) {
      if (!('replace' in options)) options.replace = true;
    }

    // Mithril 2 does not completely rerender the page if a route change leads
    // to the same route.
    // Here, the `force` parameter will use Mithril's key system to force a full
    // rerender. See https://mithril.js.org/route.html#key-parameter
    // Routing to a different route handled by the same component will rerender
    // the whole page regardless of this option.
    if (extract(attrs, 'force')) {
      if (!('state' in options)) options.state = {};
      options.state.forceRefresh = true;
    }

    attrs.options = options;

    return <m.route.Link {...attrs}>{children}</m.route.Link>;
  }
}
