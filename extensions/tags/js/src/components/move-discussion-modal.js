import Component from 'flarum/component';
import DiscussionPage from 'flarum/components/discussion-page';
import icon from 'flarum/helpers/icon';
import categoryLabel from 'categories/helpers/category-label';

export default class MoveDiscussionModal extends Component {
  constructor(props) {
    super(props);

    this.categories = m.prop(app.store.all('categories'));
  }

  view() {
    var discussion = this.props.discussion;
    var discussionCategory = discussion && discussion.category();

    return m('div.modal-dialog.modal-move-discussion', [
      m('div.modal-content', [
        m('button.btn.btn-icon.btn-link.close.back-control', {onclick: app.modal.close.bind(app.modal)}, icon('times')),
        m('div.modal-header', m('h3.title-control', discussion
          ? ['Move ', m('em', discussion.title()), ' from ', categoryLabel(discussionCategory), ' to...']
          : ['Start a Discussion In...'])),
        m('div', [
          m('ul.category-list', [
            this.categories().map(category =>
              (discussion && discussionCategory && category.id() === discussionCategory.id()) ? '' : m('li.category-tile', {style: 'background-color: '+category.color()}, [
                m('a[href=javascript:;]', {onclick: this.save.bind(this, category)}, [
                  m('h3.title', category.title()),
                  m('p.description', category.description()),
                  m('span.count', category.discussionsCount()+' discussions'),
                ])
              ])
            )
          ])
        ])
      ])
    ]);
  }

  save(category) {
    var discussion = this.props.discussion;

    if (discussion) {
      discussion.save({links: {category}}).then(discussion => {
        if (app.current instanceof DiscussionPage) {
          app.current.stream.sync();
        }
        m.redraw();
      });
    }

    this.props.onchange && this.props.onchange(category);

    app.modal.close();

    m.redraw.strategy('none');
  }
}
