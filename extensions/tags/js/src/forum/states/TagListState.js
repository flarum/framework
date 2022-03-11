export default class TagListState {
    constructor() {
        this.loadedIncludes = new Set();
    }

    async load(includes = []) {
        const unloadedIncludes = includes.filter(include => !this.loadedIncludes.has(include));

        if (unloadedIncludes.length === 0) {
            return Promise.resolve(app.store.all('tags'));
        }

        return app.store
            .find('tags', { include: unloadedIncludes.join(',') })
            .then(val => {
                unloadedIncludes.forEach(include => this.loadedIncludes.add(include));
                return val;
            });
    }
}