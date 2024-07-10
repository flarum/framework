import app from 'flarum/forum/app';
import { initialize } from '../common/index';

app.initializers.add('flarum-markdown', initialize);

// Expose compat API
import markdownCompat from './compat';
import { compat } from '@flarum/core/forum';

Object.assign(compat, markdownCompat);
