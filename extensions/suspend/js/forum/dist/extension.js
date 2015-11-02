System.register('flarum/suspend/components/SuspendUserModal', ['flarum/components/Modal', 'flarum/components/Button'], function (_export) {
  'use strict';

  var Modal, Button, SuspendUserModal;
  return {
    setters: [function (_flarumComponentsModal) {
      Modal = _flarumComponentsModal['default'];
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton['default'];
    }],
    execute: function () {
      SuspendUserModal = (function (_Modal) {
        babelHelpers.inherits(SuspendUserModal, _Modal);

        function SuspendUserModal() {
          babelHelpers.classCallCheck(this, SuspendUserModal);
          babelHelpers.get(Object.getPrototypeOf(SuspendUserModal.prototype), 'constructor', this).apply(this, arguments);
        }

        babelHelpers.createClass(SuspendUserModal, [{
          key: 'init',
          value: function init() {
            babelHelpers.get(Object.getPrototypeOf(SuspendUserModal.prototype), 'init', this).call(this);

            var until = this.props.user.suspendUntil();
            var status = null;

            if (new Date() > until) until = null;

            if (until) {
              if (until.getFullYear() === 9999) status = 'indefinitely';else status = 'limited';
            }

            this.status = m.prop(status);
            this.daysRemaining = m.prop(status === 'limited' && -moment().diff(until, 'days') + 1);
          }
        }, {
          key: 'className',
          value: function className() {
            return 'SuspendUserModal Modal--small';
          }
        }, {
          key: 'title',
          value: function title() {
            return 'Suspend ' + this.props.user.username();
          }
        }, {
          key: 'content',
          value: function content() {
            var _this = this;

            return m(
              'div',
              { className: 'Modal-body' },
              m(
                'div',
                { className: 'Form' },
                m(
                  'div',
                  { className: 'Form-group' },
                  m(
                    'label',
                    null,
                    'Suspension Status'
                  ),
                  m(
                    'div',
                    null,
                    m(
                      'label',
                      { className: 'checkbox' },
                      m('input', { type: 'radio', name: 'status', checked: !this.status(), onclick: m.withAttr('value', this.status) }),
                      'Not suspended'
                    ),
                    m(
                      'label',
                      { className: 'checkbox' },
                      m('input', { type: 'radio', name: 'status', checked: this.status() === 'indefinitely', value: 'indefinitely', onclick: m.withAttr('value', this.status) }),
                      'Suspended indefinitely'
                    ),
                    m(
                      'label',
                      { className: 'checkbox SuspendUserModal-days' },
                      m('input', { type: 'radio', name: 'status', checked: this.status() === 'limited', value: 'limited', onclick: function (e) {
                          _this.status(e.target.value);
                          m.redraw(true);
                          _this.$('.SuspendUserModal-days-input input').select();
                          m.redraw.strategy('none');
                        } }),
                      'Suspended for a limited time...',
                      this.status() === 'limited' ? m(
                        'div',
                        { className: 'SuspendUserModal-days-input' },
                        m('input', { type: 'number',
                          min: '0',
                          value: this.daysRemaining(),
                          oninput: m.withAttr('value', this.daysRemaining),
                          className: 'FormControl' }),
                        ' days'
                      ) : ''
                    )
                  )
                ),
                m(
                  'div',
                  { className: 'Form-group' },
                  m(
                    Button,
                    { className: 'Button Button--primary', loading: this.loading, type: 'submit' },
                    'Save Changes'
                  )
                )
              )
            );
          }
        }, {
          key: 'onsubmit',
          value: function onsubmit(e) {
            var _this2 = this;

            e.preventDefault();

            this.loading = true;

            var suspendUntil = null;
            switch (this.status()) {
              case 'indefinitely':
                suspendUntil = new Date('9999-12-31');
                break;

              case 'limited':
                suspendUntil = moment().add(this.daysRemaining(), 'days').toDate();
                break;

              default:
              // no default
            }

            this.props.user.save({ suspendUntil: suspendUntil }).then(function () {
              return _this2.hide();
            }, this.loaded.bind(this));
          }
        }]);
        return SuspendUserModal;
      })(Modal);

      _export('default', SuspendUserModal);
    }
  };
});;
System.register('flarum/suspend/main', ['flarum/extend', 'flarum/app', 'flarum/utils/UserControls', 'flarum/components/Button', 'flarum/components/Badge', 'flarum/Model', 'flarum/models/User', 'flarum/suspend/components/SuspendUserModal'], function (_export) {
  'use strict';

  var extend, app, UserControls, Button, Badge, Model, User, SuspendUserModal;
  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumApp) {
      app = _flarumApp['default'];
    }, function (_flarumUtilsUserControls) {
      UserControls = _flarumUtilsUserControls['default'];
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton['default'];
    }, function (_flarumComponentsBadge) {
      Badge = _flarumComponentsBadge['default'];
    }, function (_flarumModel) {
      Model = _flarumModel['default'];
    }, function (_flarumModelsUser) {
      User = _flarumModelsUser['default'];
    }, function (_flarumSuspendComponentsSuspendUserModal) {
      SuspendUserModal = _flarumSuspendComponentsSuspendUserModal['default'];
    }],
    execute: function () {

      app.initializers.add('flarum-suspend', function () {
        User.prototype.canSuspend = Model.attribute('canSuspend');
        User.prototype.suspendUntil = Model.attribute('suspendUntil', Model.transformDate);

        extend(UserControls, 'moderationControls', function (items, user) {
          if (user.canSuspend()) {
            items.add('suspend', Button.component({
              children: 'Suspend',
              icon: 'ban',
              onclick: function onclick() {
                return app.modal.show(new SuspendUserModal({ user: user }));
              }
            }));
          }
        });

        extend(User.prototype, 'badges', function (items) {
          var until = this.suspendUntil();

          if (new Date() < until) {
            items.add('suspended', Badge.component({
              icon: 'ban',
              type: 'suspended',
              label: 'Suspended'
            }));
          }
        });
      });
    }
  };
});