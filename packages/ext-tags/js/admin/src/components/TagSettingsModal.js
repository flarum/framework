import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';
import saveConfig from 'flarum/utils/saveConfig';

export default class TagSettingsModal extends Modal {
  constructor(...args) {
    super(...args);

    this.minPrimaryTags = m.prop(app.config['tags.min_primary_tags'] || 0);
    this.maxPrimaryTags = m.prop(app.config['tags.max_primary_tags'] || 0);
    this.minSecondaryTags = m.prop(app.config['tags.min_secondary_tags'] || 0);
    this.maxSecondaryTags = m.prop(app.config['tags.max_secondary_tags'] || 0);
  }

  setMinTags(minTags, maxTags, value) {
    minTags(value);
    maxTags(Math.max(value, maxTags()));
  }

  className() {
    return 'TagSettingsModal Modal--small';
  }

  title() {
    return 'Tag Settings';
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>Required Number of Primary Tags</label>
            <div className="helpText">
              Enter the minimum and maximum number of primary tags that may be applied to a discussion.
            </div>
            <div className="TagSettingsModal-rangeInput">
              <input className="FormControl"
                type="number"
                min="0"
                value={this.minPrimaryTags()}
                oninput={m.withAttr('value', this.setMinTags.bind(this, this.minPrimaryTags, this.maxPrimaryTags))}
              />
              {' to '}
              <input className="FormControl"
                type="number"
                min={this.minPrimaryTags()}
                value={this.maxPrimaryTags()}
                oninput={m.withAttr('value', this.maxPrimaryTags)}
              />
            </div>
          </div>

          <div className="Form-group">
            <label>Required Number of Secondary Tags</label>
            <div className="helpText">
              Enter the minimum and maximum number of secondary tags that may be applied to a discussion.
            </div>
            <div className="TagSettingsModal-rangeInput">
              <input className="FormControl"
                type="number"
                min="0"
                value={this.minSecondaryTags()}
                oninput={m.withAttr('value', this.setMinTags.bind(this, this.minSecondaryTags, this.maxSecondaryTags))}
              />
              {' to '}
              <input className="FormControl"
                type="number"
                min={this.minSecondaryTags()}
                value={this.maxSecondaryTags()}
                oninput={m.withAttr('value', this.maxSecondaryTags)}
              />
            </div>
          </div>

          <div className="Form-group">
            {Button.component({
              type: 'submit',
              className: 'Button Button--primary TagSettingsModal-save',
              loading: this.loading,
              children: 'Save Changes'
            })}
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    saveConfig({
      'tags.min_primary_tags': this.minPrimaryTags(),
      'tags.max_primary_tags': this.maxPrimaryTags(),
      'tags.min_secondary_tags': this.minSecondaryTags(),
      'tags.max_secondary_tags': this.maxSecondaryTags()
    }).then(
      () => this.hide(),
      () => {
        this.loading = false;
        m.redraw();
      }
    );
  }
}
