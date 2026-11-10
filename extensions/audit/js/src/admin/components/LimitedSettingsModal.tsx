import type Mithril from 'mithril';
import app from 'flarum/admin/app';
import SettingsModal from 'flarum/admin/components/SettingsModal';
import Switch from 'flarum/common/components/Switch';

interface ActionDefinition {
  key: string;
  extension: string;
}

type Group = 'user' | 'discussion' | 'post' | 'other';

const GROUPS: Group[] = ['user', 'discussion', 'post', 'other'];

export default class LimitedSettingsModal extends SettingsModal {
  className() {
    return 'Modal--AuditLogLimitedSettings';
  }

  title() {
    return app.translator.trans('flarum-audit.admin.limitedSettings.title');
  }

  form() {
    const actions: {
      [key: string]: ActionDefinition[];
    } = {
      user: [],
      discussion: [],
      post: [],
      other: [],
    };

    Object.keys(app.data.auditLogActions).forEach((extension) => {
      app.data.auditLogActions[extension].forEach((key: string) => {
        const prefix = key.split('.')[0];

        const group = typeof actions[prefix] !== 'undefined' ? prefix : 'other';

        actions[group].push({
          key,
          extension,
        });
      });
    });

    GROUPS.forEach((group) => {
      actions[group].sort((a, b) => (a.key > b.key ? 1 : -1));
    });

    const settingValue = this.setting('flarum-audit.limitedActions')();

    let enabledActions: string[] = settingValue ? settingValue.split(',') : [];

    function enableAll(exceptGroup: string | null = null, exceptOtherAction: string | null = null) {
      enabledActions = [
        ...GROUPS.filter((g) => (exceptGroup ? g !== exceptGroup : true) && g !== 'other').map((g) => g + '.*'),
        ...actions.other.filter((a) => (exceptOtherAction ? a.key !== exceptOtherAction : true)).map((a) => a.key),
      ];
    }

    const enabledExtensions = JSON.parse(app.data.settings.extensions_enabled);

    function actionLabel(action: ActionDefinition): Mithril.Children {
      const extensionActive = action.extension === 'core' || enabledExtensions.indexOf(action.extension) !== -1;

      return [
        <code>{action.key}</code>,
        extensionActive
          ? null
          : [
              ' ',
              <em>
                {app.translator.trans('flarum-audit.admin.limitedSettings.requiresExtension', {
                  extension: action.extension,
                })}
              </em>,
            ],
      ];
    }

    return [
      <p>{app.translator.trans('flarum-audit.admin.limitedSettings.introduction')}</p>,
      <div className="Form-group">
        <Switch
          state={this.setting('flarum-audit.limitedIpAddress')() === '1'}
          onchange={(value: string) => {
            this.setting('flarum-audit.limitedIpAddress')(value ? '1' : '0');
          }}
        >
          {app.translator.trans('flarum-audit.admin.settings.limitedIpAddress')}
        </Switch>
      </div>,
      GROUPS.map((group) => {
        if (group === 'other') {
          return null;
        }

        const allEnabled = enabledActions.length === 0 || enabledActions.indexOf(group + '.*') !== -1;

        return [
          <div className="Form-group AuditCheckbox">
            <Switch
              state={allEnabled}
              onchange={() => {
                if (allEnabled) {
                  if (enabledActions.length === 0) {
                    enableAll(group);
                  } else {
                    enabledActions = enabledActions.filter((pattern) => pattern !== group + '.*');
                  }
                } else {
                  // Remove any individually listed action from that prefix before adding wildcard
                  enabledActions = enabledActions.filter((a) => a.indexOf(group) === -1);
                  enabledActions.push(group + '.*');
                }

                this.setting('flarum-audit.limitedActions')(enabledActions.join(','));
              }}
            >
              <code>{group + '.*'}</code>
            </Switch>
          </div>,
          actions[group].map((action) => {
            const actionEnabled = allEnabled || enabledActions.indexOf(action.key) !== -1;

            return (
              <div className="Form-group AuditCheckbox AuditSubCheckbox">
                <Switch
                  state={actionEnabled}
                  onchange={() => {
                    const allOtherActionKeys = actions[group].filter((a) => a.key !== action.key).map((a) => a.key);

                    if (actionEnabled) {
                      if (enabledActions.length === 0) {
                        enableAll(group);
                        enabledActions.push(...allOtherActionKeys);
                      } else if (allEnabled) {
                        const index = enabledActions.indexOf(group + '.*');

                        enabledActions.splice(index, 1);
                        enabledActions.push(...allOtherActionKeys);
                      } else {
                        const index = enabledActions.indexOf(action.key);

                        enabledActions.splice(index, 1);
                      }
                    } else {
                      enabledActions.push(action.key);
                    }

                    this.setting('flarum-audit.limitedActions')(enabledActions.join(','));
                  }}
                >
                  {actionLabel(action)}
                </Switch>
              </div>
            );
          }),
        ];
      }),
      actions.other.map((action) => {
        const actionEnabled = enabledActions.length === 0 || enabledActions.indexOf(action.key) !== -1;

        return (
          <div className="Form-group AuditCheckbox">
            <Switch
              state={actionEnabled}
              onchange={() => {
                if (actionEnabled) {
                  if (enabledActions.length === 0) {
                    enableAll(null, action.key);
                  } else {
                    const index = enabledActions.indexOf(action.key);

                    enabledActions.splice(index, 1);
                  }
                } else {
                  enabledActions.push(action.key);
                }

                this.setting('flarum-audit.limitedActions')(enabledActions.join(','));
              }}
            >
              {actionLabel(action)}
            </Switch>
          </div>
        );
      }),
    ];
  }
}
