import QueueState from './QueueState';
import ControlSectionState from './ControlSectionState';

export default class ExtensionManagerState {
  public queue: QueueState = new QueueState();
  public control: ControlSectionState = new ControlSectionState();
}
