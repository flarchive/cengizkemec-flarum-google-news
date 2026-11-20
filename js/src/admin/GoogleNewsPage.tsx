import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Button from 'flarum/common/components/Button';
import type Mithril from 'mithril';

import { PREFIX, t } from './settings';

interface CacheStatus {
  cached: boolean;
  built_at: string | null;
  entries: number;
  ttl: number | null;
  stale: boolean | null;
}

interface StatusResponse {
  enabled: boolean;
  fof_blog: {
    installed: boolean;
    enabled: boolean;
    integration_enabled: boolean;
    version: string | null;
  };
  features: Record<string, boolean>;
  cache: {
    store: string;
    entries: Record<string, CacheStatus>;
    last_refresh: string | null;
  };
  errors: Array<{ time: string; message: string; class: string }>;
  compatibility: {
    flarum_version: string;
    php_version: string;
    extension_version: string | null;
    seo_extensions: string[];
    fof_seo_enabled: boolean;
    compatibility_mode: boolean;
  };
  endpoints: Record<string, string>;
  resources: Record<string, string>;
}

export default class GoogleNewsPage extends ExtensionPage {
  status: StatusResponse | null = null;
  statusLoading = false;
  statusError = false;

  oninit(vnode: Mithril.Vnode<any, this>) {
    super.oninit(vnode);
    this.loadStatus();
  }

  className(): string {
    return 'GoogleNewsPage '.concat(super.className());
  }

  content(vnode: Mithril.VnodeDOM<any, this>): JSX.Element {
    return (
      <div className="GoogleNewsPage-body">
        {super.content(vnode)}
        {this.statusSection()}
      </div>
    );
  }

  loadStatus() {
    this.statusLoading = true;
    this.statusError = false;

    app
      .request({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/google-news/status',
      })
      .then((response) => {
        this.status = response as StatusResponse;
      })
      .catch(() => {
        this.statusError = true;
      })
      .finally(() => {
        this.statusLoading = false;
        m.redraw();
      });
  }

  clearCache() {
    app
      .request({
        method: 'DELETE',
        url: app.forum.attribute('apiUrl') + '/google-news/cache',
      })
      .then(() => {
        app.alerts.show({ type: 'success' }, t('status.cache_cleared'));
        this.loadStatus();
      });
  }

  statusSection(): JSX.Element {
    if (this.statusLoading && this.status === null) {
      return (
        <div className="container GoogleNewsPage-status">
          <h2 className="GoogleNewsSectionTitle">{t('status.heading')}</h2>
          <p className="helpText">{t('status.loading')}</p>
        </div>
      );
    }

    if (this.statusError || this.status === null) {
      return (
        <div className="container GoogleNewsPage-status">
          <h2 className="GoogleNewsSectionTitle">{t('status.heading')}</h2>
          <p className="helpText">{t('status.load_failed')}</p>
          <Button className="Button" onclick={() => this.loadStatus()}>
            {t('status.refresh')}
          </Button>
        </div>
      );
    }

    const status = this.status;
    const cacheRows = Object.entries(status.cache.entries);

    return (
      <div className="container GoogleNewsPage-status">
        <div className="GoogleNewsSectionHeader">
          <h2 className="GoogleNewsSectionTitle">{t('status.heading')}</h2>
          <div className="GoogleNewsSectionActions">
            <Button className="Button" icon="fas fa-sync" onclick={() => this.loadStatus()} loading={this.statusLoading}>
              {t('status.refresh')}
            </Button>
            <Button className="Button Button--danger" icon="fas fa-trash-alt" onclick={() => this.clearCache()}>
              {t('status.clear_cache')}
            </Button>
          </div>
        </div>

        <div className="GoogleNewsStatus-grid">
          {this.statusCard(t('status.enabled'), status.enabled ? t('status.enabled') : t('status.disabled'), status.enabled ? 'success' : 'muted')}
          {this.statusCard(
            'FoF Blog',
            status.fof_blog.installed
              ? `${status.fof_blog.enabled ? t('status.enabled') : t('status.disabled')} · ${status.fof_blog.version ?? ''}`
              : t('status.not_installed'),
            status.fof_blog.installed && status.fof_blog.enabled ? 'success' : 'muted'
          )}
          {this.statusCard(t('status.news_sitemap'), status.features.news_sitemap ? t('status.enabled') : t('status.disabled'), status.features.news_sitemap ? 'success' : 'muted')}
          {this.statusCard(t('status.content_sitemap'), status.features.content_sitemap ? t('status.enabled') : t('status.disabled'), status.features.content_sitemap ? 'success' : 'muted')}
          {this.statusCard(t('status.rss'), status.features.rss ? t('status.enabled') : t('status.disabled'), status.features.rss ? 'success' : 'muted')}
          {this.statusCard(t('status.atom'), status.features.atom ? t('status.enabled') : t('status.disabled'), status.features.atom ? 'success' : 'muted')}
          {this.statusCard(t('status.structured_data'), status.features.structured_data ? t('status.enabled') : t('status.disabled'), status.features.structured_data ? 'success' : 'muted')}
          {this.statusCard(t('status.last_refresh'), status.cache.last_refresh ? this.formatDate(status.cache.last_refresh) : t('status.never'), status.cache.last_refresh ? 'success' : 'muted')}
          {this.statusCard(t('status.cache_store'), status.cache.store, 'muted')}
          {this.statusCard(
            t('status.seo_extensions'),
            status.compatibility.seo_extensions.length ? status.compatibility.seo_extensions.join(', ') : t('status.none'),
            'muted'
          )}
        </div>

        <h3 className="GoogleNewsSubTitle">{t('status.cached')}</h3>
        <table className="GoogleNewsTable">
          <thead>
            <tr>
              <th>{t('status.document')}</th>
              <th>{t('status.cached')}</th>
              <th>{t('status.entries')}</th>
              <th>{t('status.last_refresh')}</th>
              <th>{t('status.stale')}</th>
            </tr>
          </thead>
          <tbody>
            {cacheRows.map(([name, cache]) => (
              <tr>
                <td>{name.replace(/_/g, ' ')}</td>
                <td>{cache.cached ? t('status.cached') : t('status.not_cached')}</td>
                <td>{cache.entries}</td>
                <td>{cache.built_at ? this.formatDate(cache.built_at) : t('status.never')}</td>
                <td>{cache.stale === null ? '-' : cache.stale ? t('status.stale') : t('status.fresh')}</td>
              </tr>
            ))}
          </tbody>
        </table>

        <h3 className="GoogleNewsSubTitle">{t('status.endpoints')}</h3>
        <ul className="GoogleNewsLinks">
          {Object.entries(status.endpoints).map(([name, url]) => (
            <li>
              <a href={url} target="_blank" rel="noopener noreferrer">
                {name}: {url}
              </a>
            </li>
          ))}
        </ul>

        <h3 className="GoogleNewsSubTitle">{t('status.resources')}</h3>
        <ul className="GoogleNewsLinks">
          <li>
            <a href={status.resources.search_console} target="_blank" rel="noopener noreferrer">
              {t('status.search_console')}
            </a>
          </li>
          <li>
            <a href={status.resources.publisher_center} target="_blank" rel="noopener noreferrer">
              {t('status.publisher_center')}
            </a>
          </li>
          <li>
            <a href={status.resources.news_sitemap_docs} target="_blank" rel="noopener noreferrer">
              {t('status.news_sitemap_docs')}
            </a>
          </li>
        </ul>

        <h3 className="GoogleNewsSubTitle">{t('status.recent_errors')}</h3>
        {status.errors.length === 0 ? (
          <p className="helpText">{t('status.no_errors')}</p>
        ) : (
          <ul className="GoogleNewsErrors">
            {status.errors.map((error) => (
              <li>
                <strong>{this.formatDate(error.time)}</strong>: {error.message}
              </li>
            ))}
          </ul>
        )}

        <p className="helpText GoogleNewsVersion">
          Flarum {status.compatibility.flarum_version} · PHP {status.compatibility.php_version} ·{' '}
          {t('status.version')} {status.compatibility.extension_version ?? '-'}
        </p>
      </div>
    );
  }

  statusCard(label: string, value: string, tone: 'success' | 'muted'): Mithril.Children {
    return (
      <div className={`GoogleNewsStatus-card GoogleNewsStatus-card--${tone}`}>
        <span className="GoogleNewsStatus-label">{label}</span>
        <strong>{value}</strong>
      </div>
    );
  }

  formatDate(value: string): string {
    try {
      return new Date(value).toLocaleString();
    } catch {
      return value;
    }
  }
}

export { PREFIX };
