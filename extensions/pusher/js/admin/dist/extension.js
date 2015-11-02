System.register('flarum/pusher/components/PusherSettingsModal', ['flarum/components/SettingsModal'], function (_export) {
  'use strict';

  var SettingsModal, PusherSettingsModal;
  return {
    setters: [function (_flarumComponentsSettingsModal) {
      SettingsModal = _flarumComponentsSettingsModal['default'];
    }],
    execute: function () {
      PusherSettingsModal = (function (_SettingsModal) {
        babelHelpers.inherits(PusherSettingsModal, _SettingsModal);

        function PusherSettingsModal() {
          babelHelpers.classCallCheck(this, PusherSettingsModal);
          babelHelpers.get(Object.getPrototypeOf(PusherSettingsModal.prototype), 'constructor', this).apply(this, arguments);
        }

        babelHelpers.createClass(PusherSettingsModal, [{
          key: 'className',
          value: function className() {
            return 'PusherSettingsModal Modal--small';
          }
        }, {
          key: 'title',
          value: function title() {
            return app.translator.trans('flarum-pusher.admin.pusher_settings.title');
          }
        }, {
          key: 'form',
          value: function form() {
            return [m(
              'div',
              { className: 'Form-group' },
              m(
                'label',
                null,
                app.translator.trans('flarum-pusher.admin.pusher_settings.app_id_label')
              ),
              m('input', { className: 'FormControl', bidi: this.setting('flarum-pusher.app_id') })
            ), m(
              'div',
              { className: 'Form-group' },
              m(
                'label',
                null,
                app.translator.trans('flarum-pusher.admin.pusher_settings.app_key_label')
              ),
              m('input', { className: 'FormControl', bidi: this.setting('flarum-pusher.app_key') })
            ), m(
              'div',
              { className: 'Form-group' },
              m(
                'label',
                null,
                app.translator.trans('flarum-pusher.admin.pusher_settings.app_secret_label')
              ),
              m('input', { className: 'FormControl', bidi: this.setting('flarum-pusher.app_secret') })
            )];
          }
        }]);
        return PusherSettingsModal;
      })(SettingsModal);

      _export('default', PusherSettingsModal);
    }
  };
});;
System.register('flarum/pusher/main', ['flarum/extend', 'flarum/app', 'flarum/pusher/components/PusherSettingsModal'], function (_export) {
  'use strict';

  var extend, app, PusherSettingsModal;
  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumApp) {
      app = _flarumApp['default'];
    }, function (_flarumPusherComponentsPusherSettingsModal) {
      PusherSettingsModal = _flarumPusherComponentsPusherSettingsModal['default'];
    }],
    execute: function () {

      app.initializers.add('flarum-pusher', function (app) {
        app.extensionSettings['flarum-pusher'] = function () {
          return app.modal.show(new PusherSettingsModal());
        };
      });
    }
  };
});