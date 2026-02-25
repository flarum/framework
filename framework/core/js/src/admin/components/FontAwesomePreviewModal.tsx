import app from '../../admin/app';
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import type Mithril from 'mithril';

const previewGroups: Array<{ labelKey: string; icons: Array<{ cls: string; label: string }> }> = [
  {
    labelKey: 'core.admin.advanced.fontawesome.preview.fa5_fa6_label',
    icons: [
      { cls: 'fas fa-house', label: 'fas' },
      { cls: 'far fa-envelope', label: 'far' },
      { cls: 'fab fa-github', label: 'fab' },
    ],
  },
  {
    labelKey: 'core.admin.advanced.fontawesome.preview.fa7_label',
    icons: [
      { cls: 'fa-solid fa-house', label: 'fa-solid' },
      { cls: 'fa-regular fa-envelope', label: 'fa-regular' },
      { cls: 'fa-brands fa-github', label: 'fa-brands' },
      { cls: 'fas fa-bus-side', label: 'fa-bus-side' },
    ],
  },
  {
    labelKey: 'core.admin.advanced.fontawesome.preview.pro_label',
    icons: [
      { cls: 'fal fa-house', label: 'Light' },
      { cls: 'fat fa-house', label: 'Thin' },
      { cls: 'fad fa-house', label: 'Duotone' },
    ],
  },
];

export default class FontAwesomePreviewModal extends Modal<IInternalModalAttrs> {
  className() {
    return 'FontAwesomePreviewModal Modal--small';
  }

  title(): Mithril.Children {
    return app.translator.trans('core.admin.advanced.fontawesome.preview.label');
  }

  content(): Mithril.Children {
    return (
      <div className="Modal-body">
        <p className="helpText">{app.translator.trans('core.admin.advanced.fontawesome.preview.help')}</p>
        <div className="FontAwesomePreview">
          {previewGroups.map((group) => (
            <div className="FontAwesomePreview-group">
              <div className="FontAwesomePreview-groupHeader">
                <span className="FontAwesomePreview-groupLabel">{app.translator.trans(group.labelKey)}</span>
                <span className="FontAwesomePreview-groupDivider" />
              </div>
              <div className="FontAwesomePreview-icons">
                {group.icons.map((icon) => (
                  <div className="FontAwesomePreview-tile" title={icon.cls}>
                    <span className="FontAwesomePreview-icon">
                      <i className={icon.cls} aria-hidden="true" />
                    </span>
                    <span className="FontAwesomePreview-iconLabel">{icon.label}</span>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
      </div>
    );
  }
}
