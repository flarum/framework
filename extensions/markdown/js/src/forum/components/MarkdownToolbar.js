import Component from 'flarum/common/Component';

export default class MarkdownToolbar extends Component {
  view(vnode) {
    return <div class="MarkdownToolbar">
      {vnode.children}
    </div>;
  }
}
