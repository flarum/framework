import Component from '../Component';

export default class Route {
    public name;
    public path;
    public component;

    constructor(name: string, path: string, component?: Component) {
        this.name = name;
        this.path = path;
        this.component = component;
    }
}
