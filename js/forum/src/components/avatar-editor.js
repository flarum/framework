import Component from 'flarum/component';
import avatar from 'flarum/helpers/avatar';
import icon from 'flarum/helpers/icon';
import listItems from 'flarum/helpers/list-items';
import ItemList from 'flarum/utils/item-list';
import ActionButton from 'flarum/components/action-button';
import LoadingIndicator from 'flarum/components/loading-indicator';

export default class AvatarEditor extends Component {
  constructor(props) {
    super(props);

    this.loading = m.prop(false);
  }

  view() {
    var user = this.props.user;

    return m('div.avatar-editor.dropdown', {
      className: (this.loading() ? 'loading' : '')+' '+(this.props.className || '')
    }, [
      avatar(user),
      m('a.dropdown-toggle[href=javascript:;][data-toggle=dropdown]', {onclick: this.quickUpload.bind(this)}, [
        this.loading() ? LoadingIndicator.component() : icon('pencil icon')
      ]),
      m('ul.dropdown-menu', listItems(this.controlItems().toArray()))
    ]);
  }

  controlItems() {
    var items = new ItemList();

    items.add('upload',
      ActionButton.component({
        icon: 'upload',
        label: 'Upload',
        onclick: this.upload.bind(this)
      })
    );

    items.add('remove',
      ActionButton.component({
        icon: 'times',
        label: 'Remove',
        onclick: this.remove.bind(this)
      })
    );

    return items;
  }

  quickUpload(e) {
    if (!this.props.user.avatarUrl()) {
      e.preventDefault();
      e.stopPropagation();
      this.upload();
    }
  }

  upload() {
    if (this.loading()) { return; }

    var $input = $('<input type="file">');
    var user = this.props.user;
    var self = this;
    $input.appendTo('body').hide().click().on('change', function() {
      var data = new FormData();
      data.append('avatar', $(this)[0].files[0]);
      self.loading(true);
      m.redraw();
      m.request({
        method: 'POST',
        url: app.config['api_url']+'/users/'+user.id()+'/avatar',
        data: data,
        serialize: data => data,
        background: true,
        config: app.session.authorize.bind(app.session)
      }).then(function(data) {
        self.loading(false);
        app.store.pushPayload(data);
        delete user.avatarColor;
        m.redraw();
      });
    });
  }

  remove() {
    var self = this;
    var user = this.props.user;
    self.loading(true);
    m.redraw();
    m.request({
      method: 'DELETE',
      url: app.config['api_url']+'/users/'+user.id()+'/avatar',
      config: app.session.authorize.bind(app.session)
    }).then(function(data) {
      self.loading(false);
      app.store.pushPayload(data);
      delete user.avatarColor;
      m.redraw();
    });
  }
}
