import NavItem from 'flarum/components/nav-item'

export default class IndexNavItem extends NavItem {
  static props(props) {
    props.onclick = props.onclick || function() {
      app.cache.discussionList = null;
      m.redraw.strategy('none');
    };
  }
}
