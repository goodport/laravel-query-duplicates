# Changelog

## 1.3.1 — 2026-11-08

- Exclusion rules now support a route, a query, or both: route-only rules exclude every query on matching routes, query-only rules exclude everywhere, and a rule with both scopes the query to matching routes.
- The matched route name is captured from `RouteMatched`, so route-scoped exclusions work with the real route instead of the request path.
- Route patterns support `*` wildcards; unnamed routes cannot match a route exclusion.
- `exclude` is now a flat list of rules (breaking: the `routes` and `queries` sections from 1.3.0 are replaced).

## 1.3.0 — 2026-11-08

- Exclude requests by route name via `exclude.routes`, including `*` wildcard patterns. A matching request is not recorded at all.
- Query exclusions moved to `exclude.queries` (breaking: the previous flat `exclude` list no longer applies).
- Configuration validation rejects unknown exclusion keys and invalid rule shapes.

## 1.2.4 — 2026-11-07

- Restored compact path and SQL columns in the admin table.

## 1.2.3 — 2026-11-07

- Shortened the Russian "last seen" column label to "Date".

## 1.2.2 — 2026-11-07

- Route names are shown in full instead of being truncated.
- Wider SQL preview in the admin table.

## 1.2.1 — 2026-11-07

- Removed the occurrences (trigger count) column and its sorting from the admin table.

## 1.2.0 — 2026-11-07

- Tracking is configured per HTTP method group: `methods.get` for GET requests and `methods.other` for every other method, including custom ones.
- Each group has its own `enabled` flag and `threshold`; GET defaults to `2`, other methods default to `5`.
- The group threshold is selected per request before collection starts and applies to both duplicates and N+1.
- A disabled group stops creating new reports; existing reports and the admin page stay available.
- Reports are still split by the actual HTTP method.
- The single `threshold` config key is replaced by `methods` (breaking).

## 1.1.9 — 2026-11-07

- README documentation updates.

## 1.1.8 — 2026-11-07

- Added an HTTP method filter to the admin page, restricted to a whitelist of methods.

## 1.1.7 / 1.1.6 — 2026-11-07

- Copy and delete actions in the admin table are now compact icon-only buttons with tooltips.
- The delete action uses danger styling.
- Copy feedback is shown in the button tooltip instead of the label.

## 1.1.5 / 1.1.4 — 2026-11-07

- Narrower actions column and vertically stacked row actions in the admin table.

## 1.1.3 — 2026-11-07

- The `method` column is sortable.
- `query_count` is the default sort column instead of `updated_at`.
- Added a stable secondary ordering by id for pagination.

## 1.1.2 — 2026-11-07

- Delete a single report from the admin table.
- The redirect keeps the current search, type, sort, direction, and page.

## 1.1.1 — 2026-11-07

- Reports are stored in a package-owned SQLite file instead of the application database connection.
- The directory, database file, and `query_duplicates` table are created automatically; no migration is required.
- Added `storage_path` config (`storage/framework/query-duplicates.sqlite` by default).
- Added `StorageUnavailableException` and a setup message on the admin page when storage is unavailable.
- Storage errors never break the HTTP response.

## 1.1.0 / 1.0.0 — 2026-11-07

- Reports moved from the log channel to a database table (breaking: `log_channel` is removed).
- Added the `QueryDuplicate` model, `query_duplicates` migration, repository with search/type filters, whitelisted sorting, and pagination.
- Added the built-in admin page with clear and per-request tracking skip for its own routes.
- Admin routes are protected by the `view-query-duplicates` gate; the page returns 403 until the application defines it.
- Reports are written with one `upsert` and one `increment` per request.
- Added English and Russian translations.
- First stable release.

## 0.1.4 — 2026-11-07

- Deduplication keys normalize numeric path segments to `{id}` and SQL literals to `?`, so requests for different records collapse into one report.

## 0.1.3 / 0.1.2 — 2026-11-07

- Added cache-based report deduplication: an identical report is written once per hour instead of on every request.

## 0.1.1 — 2026-11-06

- Exclusion rules accept a SQL substring or a rule with `sql` and optional `bindings`; only the listed binding positions are compared.
- Added validation for exclusion configuration.
- Added agent documentation.

## 0.1.0 — 2026-11-05

- Initial release.
- Detect duplicate SQL queries and N+1 queries inside HTTP requests.
- Reports are written to a configurable log channel.
- Strict mode ignores bindings when comparing repeated SQL.
- Configurable execution threshold and SQL-substring exclusions.
- N+1 detection based on repeated lazy relation loads.
