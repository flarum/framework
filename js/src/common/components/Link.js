import Component from '../Component';
import extract from '../utils/extract';

/**
 * The link component enables both internal and external links.
 * It will return a regular HTML link for any links to external sites,
 * and it will use Mithril's m.route.Link for any internal links.
 *
 * Please note that absolute links to the current site will be returned as regular HTML
 * links. Otherwise, linking to other sites or SPAs (or even the Flarum admin dashboard)
 * wouldn't work.
 */
export default class Link extends Component {
  view(vnode) {
    let { options = {}, ...attrs } = vnode.attrs;

    attrs.href = attrs.href || '';

    // For some reason, m.route.Link does not like vnode.text, so if present, we
    // need to convert it to text vnodes and store it in children.
    const children = vnode.children || { tag: '#', children: vnode.text };

    // If an absolute url is being used, we will return a plain old link.
    // This saves us from having to do additional checks in components
    // that might use internal OR external urls.
    if (attrs.href.includes('://')) {
      return <a {...attrs}>{children}</a>;
    }

    // If the href URL of the link is the same as the current page path
    // we will not add a new entry to the browser history.
    // This allows us to still refresh the Page component
    // without adding endless history entries.
    if (attrs.href === m.route.get()) {
      if (!('replace' in options)) options.replace = true;
    }

    // Mithril 2 does not completely rerender the page if a route change leads to the same route
    // (or the same component handling a different route).
    // Here, the `force` parameter will use Mithril's key system to force a full rerender
    // see https://mithril.js.org/route.html#key-parameter
    if (extract(attrs, 'force')) {
      if (!('state' in options)) options.state = {};
      if (!('key' in options.state)) options.state.key = Date.now();
    }

    attrs.options = options;

    return <m.route.Link {...attrs}>{children}</m.route.Link>;
  }
}
