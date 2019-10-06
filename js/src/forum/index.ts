import Forum from './Forum';

const app = new Forum();

// @ts-ignore
window.app = app;

app.bus.subscribe('app.plugins', () => {
    // @ts-ignore
    const extensions = flarum.extensions;

    Object.keys(extensions).forEach(name => {
        const extension = extensions[name];

        // if (typeof extension === 'function') extension();
    });
});

export { app };

// Export compat API
import compat from './compat';

compat.app = app;

export { compat };
