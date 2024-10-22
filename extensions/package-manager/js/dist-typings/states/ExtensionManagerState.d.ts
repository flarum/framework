import QueueState from './QueueState';
import ControlSectionState from './ControlSectionState';
import ExtensionListState from './ExtensionListState';
export default class ExtensionManagerState {
    queue: QueueState;
    control: ControlSectionState;
    extensions: ExtensionListState;
}
