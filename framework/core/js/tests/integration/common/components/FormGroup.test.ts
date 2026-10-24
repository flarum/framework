import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import FormGroup from '../../../../src/common/components/FormGroup';
import m from 'mithril';
import mq from 'mithril-query';
import Stream from '../../../../src/common/utils/Stream';

beforeAll(() => bootstrapForum());

describe('FormGroup boolean/switch type', () => {
  it('renders a Switch component', () => {
    const stream = Stream(false);
    const group = mq(m(FormGroup, { type: 'bool', label: 'Enable feature', stream }));

    expect(group).toHaveElement('.Form-group');
    expect(group).toHaveElement('.Checkbox--switch');
  });

  it('renders help text when provided', () => {
    const stream = Stream(false);
    const group = mq(m(FormGroup, { type: 'bool', label: 'Enable feature', help: 'This helps you.', stream }));

    expect(group).toHaveElement('.helpText');
    expect(group).toContainRaw('This helps you.');
  });

  it('passes aria-describedby to the switch input when help text is provided', () => {
    const stream = Stream(false);
    const group = mq(m(FormGroup, { type: 'bool', label: 'Enable feature', help: 'This helps you.', stream }));

    const helpEl = group.first('.helpText') as HTMLElement;
    expect(helpEl).not.toBeNull();

    const helpId = helpEl.id;
    expect(helpId).toBeTruthy();

    expect(group).toHaveElementAttr('input[type="checkbox"]', 'aria-describedby', helpId);
  });

  it('does not add aria-describedby when no help text', () => {
    const stream = Stream(false);
    const group = mq(m(FormGroup, { type: 'bool', label: 'Enable feature', stream }));

    expect(group).not.toHaveElement('.helpText');
    // Input should not have aria-describedby (or it should be absent/empty)
    const inputEl = group.first('input[type="checkbox"]');
    expect(inputEl?.attrs?.['aria-describedby']).toBeFalsy();
  });
});

describe('FormGroup icon type', () => {
  it('renders an IconInput: the field plus the picker toggle', () => {
    const stream = Stream('fas fa-bolt');
    const group = mq(m(FormGroup, { type: 'icon', label: 'Icon', help: 'Any Font Awesome class.', stream }));

    // The stream reaches the field through `bidi`, which this environment does
    // not wire up; IconInput's own tests cover the value.
    expect(group).toHaveElement('.IconInput input.FormControl');
    expect(group).toHaveElement('.IconInput-toggle');
  });

  it('labels and describes the text field', () => {
    const group = mq(m(FormGroup, { type: 'icon', label: 'Icon', help: 'Any Font Awesome class.', stream: Stream('') }));

    const input = group.first('.IconInput input.FormControl') as HTMLInputElement;
    const label = group.first('label') as HTMLLabelElement;
    const help = group.first('.helpText') as HTMLElement;

    expect(label.getAttribute('for')).toBe(input.id);
    expect(input.getAttribute('aria-describedby')).toBe(help.id);
  });
});
