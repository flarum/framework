import type IExtender from './IExtender';
import type { IExtensionModule } from './IExtender';
import type Application from '../Application';
import IGambit from '../query/IGambit';
export default class Search implements IExtender {
    protected gambits: Record<string, Array<new () => IGambit>>;
    gambit(modelType: string, gambit: new () => IGambit): this;
    extend(app: Application, extension: IExtensionModule): void;
}
