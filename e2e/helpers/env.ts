/**
 * Deterministic defaults for wp-env (see repo .wp-env.json). Override with env for CI.
 */
export const WP_DEFAULT_BASE_URL = process.env.WP_BASE_URL ?? 'http://localhost:8888';
export const WP_ADMIN_USER = process.env.WP_ADMIN_USER ?? 'admin';
export const WP_ADMIN_PASSWORD = process.env.WP_ADMIN_PASSWORD ?? 'password';

/** Created by e2e/global-setup.ts via wp-env CLI when wp-env is available. */
export const WP_SUBSCRIBER_USER = process.env.WP_SUBSCRIBER_USER ?? 'aio_e2e_subscriber';
export const WP_SUBSCRIBER_PASSWORD = process.env.WP_SUBSCRIBER_PASSWORD ?? 'password';
