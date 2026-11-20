import app from 'flarum/admin/app';

export const PREFIX = 'cengizkemec-google-news.';

const t = (key: string): string => app.translator.trans(`cengizkemec-google-news.admin.${key}`) as string;

export type SettingDefinition = {
  priority: number;
  build: () => Record<string, unknown>;
};

export type SectionDefinition = {
  priority: number;
  label: string;
};

export const sections: SectionDefinition[] = [
  { priority: 1000, label: 'sections.general' },
  { priority: 800, label: 'sections.sitemap' },
  { priority: 600, label: 'sections.feeds' },
  { priority: 400, label: 'sections.structured_data' },
  { priority: 200, label: 'sections.seo' },
  { priority: 100, label: 'sections.publishing' },
];

const bool = (key: string, priority: number, label: string, help: string): SettingDefinition => ({
  priority,
  build: () => ({
    setting: PREFIX + key,
    type: 'boolean',
    label: t(label),
    help: t(help),
  }),
});

const text = (key: string, priority: number, label: string, help: string, type = 'text'): SettingDefinition => ({
  priority,
  build: () => ({
    setting: PREFIX + key,
    type,
    label: t(label),
    help: t(help),
  }),
});

const number = (key: string, priority: number, label: string, help: string, min?: number, max?: number): SettingDefinition => ({
  priority,
  build: () => ({
    setting: PREFIX + key,
    type: 'number',
    min,
    max,
    label: t(label),
    help: t(help),
  }),
});

export const settings: SettingDefinition[] = [
  bool('enabled', 990, 'settings.enabled_label', 'settings.enabled_help'),
  bool('fof_blog_integration', 980, 'settings.fof_blog_label', 'settings.fof_blog_help'),
  text('default_language', 970, 'settings.default_language_label', 'settings.default_language_help'),
  text('publisher_name', 960, 'settings.publisher_name_label', 'settings.publisher_name_help'),
  text('publisher_website', 950, 'settings.publisher_website_label', 'settings.publisher_website_help', 'url'),
  text('publisher_country', 940, 'settings.publisher_country_label', 'settings.publisher_country_help'),
  {
    priority: 930,
    build: () => ({
      setting: PREFIX + 'publisher_logo',
      type: 'image-upload',
      name: 'google-news-logo',
      routePath: 'google-news/logo',
      value: () => app.data.settings[PREFIX + 'publisher_logo'],
      url: () => app.forum.attribute('googleNewsPublisherLogoUrl'),
      label: t('settings.publisher_logo_label'),
      help: t('settings.publisher_logo_help'),
    }),
  },
  text('publisher_logo_url', 920, 'settings.publisher_logo_url_label', 'settings.publisher_logo_url_help', 'url'),
  text('default_author', 910, 'settings.default_author_label', 'settings.default_author_help'),

  bool('sitemap.enabled', 790, 'settings.sitemap_enabled_label', 'settings.sitemap_enabled_help'),
  bool('sitemap.content_enabled', 780, 'settings.content_sitemap_enabled_label', 'settings.content_sitemap_enabled_help'),
  number('sitemap.cache_duration', 770, 'settings.cache_duration_label', 'settings.cache_duration_help', 30, 86400),
  number('sitemap.news_window_hours', 760, 'settings.news_window_label', 'settings.news_window_help', 1, 48),
  number('sitemap.max_news_entries', 750, 'settings.max_news_entries_label', 'settings.max_news_entries_help', 1, 1000),
  number('sitemap.content_max_urls', 740, 'settings.content_max_urls_label', 'settings.content_max_urls_help', 1, 50000),

  bool('feeds.rss_enabled', 590, 'settings.rss_enabled_label', 'settings.rss_enabled_help'),
  bool('feeds.atom_enabled', 580, 'settings.atom_enabled_label', 'settings.atom_enabled_help'),
  number('feeds.limit', 570, 'settings.feed_limit_label', 'settings.feed_limit_help', 1, 500),
  text('feeds.description', 560, 'settings.feed_description_label', 'settings.feed_description_help', 'textarea'),
  bool('feeds.include_regular_posts', 550, 'settings.include_regular_posts_label', 'settings.include_regular_posts_help'),

  bool('schema.news_article', 390, 'settings.schema_news_article_label', 'settings.schema_news_article_help'),
  bool('schema.blog_posting', 380, 'settings.schema_blog_posting_label', 'settings.schema_blog_posting_help'),
  bool('schema.article', 370, 'settings.schema_article_label', 'settings.schema_article_help'),
  bool('schema.organization', 360, 'settings.schema_organization_label', 'settings.schema_organization_help'),
  bool('schema.website', 350, 'settings.schema_website_label', 'settings.schema_website_help'),
  bool('schema.breadcrumb', 340, 'settings.schema_breadcrumb_label', 'settings.schema_breadcrumb_help'),
  bool('schema.compatibility_mode', 330, 'settings.compatibility_mode_label', 'settings.compatibility_mode_help'),

  bool('seo.open_graph', 190, 'settings.open_graph_label', 'settings.open_graph_help'),
  {
    priority: 185,
    build: () => ({
      setting: PREFIX + 'seo.open_graph_type',
      type: 'select',
      default: 'article',
      options: {
        article: t('settings.open_graph_type_options.article'),
        website: t('settings.open_graph_type_options.website'),
      },
      label: t('settings.open_graph_type_label'),
      help: t('settings.open_graph_type_help'),
    }),
  },
  bool('seo.twitter', 180, 'settings.twitter_label', 'settings.twitter_help'),
  bool('seo.canonical', 170, 'settings.canonical_label', 'settings.canonical_help'),
  bool('seo.description', 160, 'settings.description_label', 'settings.description_help'),
  {
    priority: 150,
    build: () => ({
      setting: PREFIX + 'seo.robots',
      type: 'select',
      default: 'index,follow',
      options: {
        'index,follow': t('settings.robots_options.index_follow'),
        'index,nofollow': t('settings.robots_options.index_nofollow'),
        'noindex,follow': t('settings.robots_options.noindex_follow'),
        'noindex,nofollow': t('settings.robots_options.noindex_nofollow'),
      },
      label: t('settings.robots_label'),
      help: t('settings.robots_help'),
    }),
  },

  bool('defaults.is_news', 90, 'settings.defaults_is_news_label', 'settings.defaults_is_news_help'),
  bool('defaults.enable_structured_data', 80, 'settings.defaults_structured_data_label', 'settings.defaults_structured_data_help'),
  bool('defaults.include_in_sitemap', 70, 'settings.defaults_sitemap_label', 'settings.defaults_sitemap_help'),
  bool('defaults.include_in_feed', 60, 'settings.defaults_feed_label', 'settings.defaults_feed_help'),
];

export { t };
