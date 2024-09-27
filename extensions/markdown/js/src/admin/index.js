import app from 'flarum/admin/app';
import { initialize } from '../common/index';

app.initializers.add('flarum-markdown', initialize);

// Expose compat API
import markdownCompat from './compat';
import { compat } from '@flarum/core/admin';

Object.assign(compat, markdownCompat);
