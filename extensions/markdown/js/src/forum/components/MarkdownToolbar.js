import Component from 'flarum/Component';

export default class MarkdownToolbar extends Component {
  view(vnode) {
    return <div class="MarkdownToolbar">
      {vnode.children}
    </div>;
  }
}
