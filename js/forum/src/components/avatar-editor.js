import Component from 'flarum/component';
import avatar from 'flarum/helpers/avatar';
import icon from 'flarum/helpers/icon';
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
      m('ul.dropdown-menu', [
        m('li', m('a[href=javascript:;]', {onclick: this.upload.bind(this)}, [icon('upload icon'), ' Upload'])),
        m('li', m('a[href=javascript:;]', {onclick: this.remove.bind(this)}, [icon('times icon'), ' Remove']))
      ])
    ]);
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
    this.props.user.pushData({avatarUrl: null});
    delete this.props.user.avatarColor;
    m.redraw();
  }
}
