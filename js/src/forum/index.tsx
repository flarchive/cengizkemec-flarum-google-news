import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import Button from 'flarum/common/components/Button';
import GoogleNewsSettingsModal from './GoogleNewsSettingsModal';

export { default as extend } from './extend';

const t = (key: string) => app.translator.trans(`cengizkemec-google-news.forum.${key}`);

function canManage(): boolean {
  return !!app.forum.attribute('canManageGoogleNews') && !!app.forum.attribute('googleNewsEnabled');
}

function openSettings(discussion: any) {
  app.modal.show(GoogleNewsSettingsModal, { discussion });
}

app.initializers.add('cengizkemec-google-news', () => {
  // Standalone forums (and regular discussions) get a moderation control.
  extend(DiscussionControls as any, 'moderationControls', function (this: any, items: any, discussion: any) {
    if (!canManage()) return;

    const extensions = (globalThis as any).flarum?.extensions ?? {};
    const blogMeta = discussion.blogMeta?.();

    if ('fof-blog' in extensions && blogMeta) {
      return;
    }

    items.add(
      'googleNews',
      <Button icon="fas fa-newspaper" onclick={() => openSettings(discussion)}>
        {t('controls.settings')}
      </Button>,
      45
    );
  });

  // FoF Blog articles use their own edit menu. The module is imported lazily so
  // the extension still works when FoF Blog is not installed.
  const extensions = (globalThis as any).flarum?.extensions ?? {};

  if ('fof-blog' in extensions) {
    import('ext:fof/blog/forum/components/BlogPostController')
      .then(({ default: BlogPostController }) => {
        extend(BlogPostController.prototype as any, 'manageArticleButtons', function (this: any, items: any) {
          if (!canManage()) return;

          const article = this.attrs.article;

          items.add(
            'googleNews',
            <Button icon="fas fa-newspaper" onclick={() => openSettings(article)}>
              {t('controls.settings')}
            </Button>,
            75
          );
        });
      })
      .catch(() => {
        // FoF Blog may be disabled at runtime; the standalone control remains.
      });
  }
});
