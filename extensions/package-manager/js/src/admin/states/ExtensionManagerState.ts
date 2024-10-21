import QueueState from './QueueState';
import ControlSectionState from './ControlSectionState';
import ExtensionListState from './ExtensionListState';

export default class ExtensionManagerState {
  public queue: QueueState = new QueueState();
  public control: ControlSectionState = new ControlSectionState();
  public extensions: ExtensionListState = new ExtensionListState();
}
