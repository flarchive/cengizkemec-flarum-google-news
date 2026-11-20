import app from 'flarum/admin/app';

export { default as extend } from './extend';

app.initializers.add('cengizkemec-google-news', () => {
  // The extension page is registered through the Admin extender in extend.ts.
});
