import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';
import saveSettings from 'flarum/utils/saveSettings';

export default class EditCustomCssModal extends Modal {
  init() {
    this.customLess = m.prop(app.settings.custom_less || '');
  }

  className() {
    return 'EditCustomCssModal Modal--large';
  }

  title() {
    return app.translator.trans('core.admin.edit_css.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <p>{app.translator.trans('core.admin.edit_css.customize_text', {a: <a href="https://github.com/flarum/core/tree/master/less" target="_blank"/>})}</p>

        <div className="Form">
          <div className="Form-group">
            <textarea className="FormControl" rows="30" value={this.customLess()} onchange={m.withAttr('value', this.customLess)}/>
          </div>

          <div className="Form-group">
            {Button.component({
              className: 'Button Button--primary',
              type: 'submit',
              children: app.translator.trans('core.admin.edit_css.submit_button'),
              loading: this.loading
            })}
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    saveSettings({
      custom_less: this.customLess()
    }).then(() => window.location.reload());
  }
}
