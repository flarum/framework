import Button, {ButtonProps} from './Button';

interface LinkButtonProps extends ButtonProps {
  active: boolean;
  oncreate: Function;
  href?: string;
}

/**
 * The `LinkButton` component defines a `Button` which links to a route.
 *
 * ### Props
 *
 * All of the props accepted by `Button`, plus:
 *
 * - `active` Whether or not the page that this button links to is currently
 *   active.
 * - `href` The URL to link to. If the current URL `m.route()` matches this,
 *   the `active` prop will automatically be set to true.
 */
export default class LinkButton extends Button<LinkButtonProps> {
  static initProps(props: LinkButtonProps) {
    props.active = this.isActive(props);
    props.oncreate = props.oncreate;
  }

  view(vnode) {
    const vdom = super.view(vnode);

    vdom.tag = m.route.Link;

    return vdom;
  }

  /**
   * Determine whether a component with the given props is 'active'.
   */
  static isActive(props: LinkButtonProps): boolean {
    return typeof props.active !== 'undefined'
      ? props.active
      : m.route.get() === props.href;
  }
}
