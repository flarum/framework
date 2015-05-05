import Component from 'flarum/component';
import SubtreeRetainer from 'flarum/utils/subtree-retainer';
import DropdownButton from 'flarum/components/dropdown-button';

export default class Post extends Component {
  constructor(props) {
    super(props);

    this.subtree = new SubtreeRetainer(
      () => this.props.post.freshness,
      () => {
        var user = this.props.post.user();
        return user && user.freshness;
      }
    );
  }

  view(content, attrs) {
    var controls = this.props.post.controls(this).toArray();

    return m('article.post', attrs, this.subtree.retain() || m('div', [
      controls.length ? DropdownButton.component({
        items: controls,
        className: 'contextual-controls',
        buttonClass: 'btn btn-default btn-icon btn-sm btn-naked',
        menuClass: 'pull-right'
      }) : '',
      content
    ]));
  }
}
