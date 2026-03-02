import app from 'flarum/admin/app';
import AdminPage, { AdminHeaderAttrs } from 'flarum/admin/components/AdminPage';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import type { IPageAttrs } from 'flarum/common/components/Page';
import type Mithril from 'mithril';
import DataType from '../models/DataType';
import Tooltip from 'flarum/common/components/Tooltip';
import ExtensionLink from './ExtensionLink';
import LinkButton from 'flarum/common/components/LinkButton';
import Form from 'flarum/common/components/Form';
import Icon from 'flarum/common/components/Icon';

type ColumnMeta = { type: string; length: number | null; default: string | null; nullable: boolean };
type UserColumnsData = {
  allColumns: Record<string, ColumnMeta>;
  removableColumns: Record<string, string | null>;
  piiKeys: string[];
  piiKeyExtensions: Record<string, string | null>;
};

export default class GdprPage<CustomAttrs extends IPageAttrs = IPageAttrs> extends AdminPage<CustomAttrs> {
  gdprDataTypes: DataType[] = [];
  userColumnsData: UserColumnsData | null = null;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.loadGdprDataTypes();
    this.loadUserColumnsData();
  }

  headerInfo(): AdminHeaderAttrs {
    return {
      className: 'GdprPage--header',
      icon: 'fas fa-user-shield',
      title: app.translator.trans('flarum-gdpr.admin.gdpr_page.heading'),
      description: app.translator.trans('flarum-gdpr.admin.gdpr_page.description'),
    };
  }

  loadGdprDataTypes() {
    this.loading = true;
    app.store.find<DataType[]>('gdpr-datatypes').then((dataTypes) => {
      this.gdprDataTypes = dataTypes;
      this.loading = false;
      m.redraw();
    });
  }

  loadUserColumnsData() {
    app
      .request<{ data: UserColumnsData }>({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/gdpr-datatypes/user-columns',
      })
      .then((response) => {
        this.userColumnsData = response.data;
        m.redraw();
      });
  }

  content(): Mithril.Children {
    if (this.loading) {
      return <LoadingIndicator />;
    }

    return (
      <div className="GdprPage">
        <Form>
          <div className="Form-group">
            <label>{app.translator.trans('flarum-gdpr.admin.gdpr_page.settings.heading')}</label>
            <p className="helpText">{app.translator.trans('flarum-gdpr.admin.gdpr_page.settings.help_text')}</p>
            <LinkButton className="Button" href={app.route('extension', { id: 'flarum-gdpr' })}>
              {app.translator.trans('flarum-gdpr.admin.gdpr_page.settings.extension_settings_button')}
            </LinkButton>
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('flarum-gdpr.admin.gdpr_page.data_types.title')}</label>
            <p className="helpText">{app.translator.trans('flarum-gdpr.admin.gdpr_page.data_types.help_text')}</p>
            {this.grid()}
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('flarum-gdpr.admin.gdpr_page.user_table_data.title')}</label>
            <p className="helpText">{app.translator.trans('flarum-gdpr.admin.gdpr_page.user_table_data.help_text')}</p>
            {this.userColumnsData ? this.userColumnTable(this.userColumnsData) : <LoadingIndicator />}
          </div>
        </Form>
      </div>
    );
  }

  grid() {
    return (
      <div className="GdprGrid">
        <div class="GdprGrid-row">
          <div className="GdprGrid-header">{app.translator.trans('flarum-gdpr.admin.gdpr_page.data_types.type')}</div>
          <div className="GdprGrid-header">{app.translator.trans('flarum-gdpr.admin.gdpr_page.data_types.export_description')}</div>
          <div className="GdprGrid-header">{app.translator.trans('flarum-gdpr.admin.gdpr_page.data_types.anonymize_description')}</div>
          <div className="GdprGrid-header">{app.translator.trans('flarum-gdpr.admin.gdpr_page.data_types.delete_description')}</div>
          <div className="GdprGrid-header">{app.translator.trans('flarum-gdpr.admin.gdpr_page.data_types.extension')}</div>
        </div>

        {this.gdprDataTypes.map((dataType) => (
          <>
            <div class="GdprGrid-row">
              <div>
                <Tooltip text={dataType.id()}>
                  <span className="helpText">{dataType.type()}</span>
                </Tooltip>
              </div>
              <div className="helpText">{dataType.exportDescription()}</div>
              <div className="helpText">{dataType.anonymizeDescription()}</div>
              <div className="helpText">{dataType.deleteDescription()}</div>
              <div>
                <ExtensionLink extension={dataType.extension() ? app.data.extensions[dataType.extension() as string] : null} />
              </div>
            </div>
          </>
        ))}
      </div>
    );
  }

  userColumnTable({ allColumns, removableColumns, piiKeys, piiKeyExtensions }: UserColumnsData): Mithril.Children {
    const t = (key: string) => app.translator.trans(`flarum-gdpr.admin.gdpr_page.user_table_data.${key}`);

    return (
      <div className="GdprGrid GdprGrid--userColumns">
        <div className="GdprGrid-row">
          <div className="GdprGrid-header">{t('column')}</div>
          <div className="GdprGrid-header">{t('type')}</div>
          <div className="GdprGrid-header">{t('nullable')}</div>
          <div className="GdprGrid-header">{t('pii')}</div>
          <div className="GdprGrid-header">{t('redacted_on_export')}</div>
          <div className="GdprGrid-header">{t('extension')}</div>
        </div>

        {Object.entries(allColumns).map(([column, meta]) => {
          const isPii = piiKeys.includes(column);
          const redactedByExtension = column in removableColumns ? removableColumns[column] : undefined;
          const isRedacted = redactedByExtension !== undefined;
          const extensionId = redactedByExtension ?? (column in piiKeyExtensions ? piiKeyExtensions[column] : null);

          return (
            <div className="GdprGrid-row">
              <div>
                <code>{column}</code>
              </div>
              <div className="helpText">{meta.type}</div>
              <div className="helpText">{meta.nullable ? t('yes') : t('no')}</div>
              <div>
                {isPii ? (
                  <Tooltip text={t('pii_tooltip')}>
                    <span className="GdprPiiBadge">
                      <Icon name="fas fa-user-secret" /> {t('yes')}
                    </span>
                  </Tooltip>
                ) : (
                  <span className="helpText">{t('no')}</span>
                )}
              </div>
              <div>
                {isRedacted ? (
                  <Tooltip text={t('redacted_on_export_tooltip')}>
                    <span>{t('yes')}</span>
                  </Tooltip>
                ) : (
                  <span className="helpText">{t('no')}</span>
                )}
              </div>
              <div>
                <ExtensionLink extension={extensionId ? (app.data.extensions[extensionId] ?? null) : null} />
              </div>
            </div>
          );
        })}
      </div>
    );
  }
}
