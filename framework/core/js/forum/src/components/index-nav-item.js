import NavItem from 'flarum/components/nav-item'

export default class IndexNavItem extends NavItem {
  static props(props) {
    props.onclick = props.onclick || function() {
      if (app.cache.discussionList) {
        app.cache.discussionList.forceReload = true;
      }
      m.redraw.strategy('none');
    };
  }
}
