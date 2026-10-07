# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.22] - 2026-10-07

### Fixed
- Admin menu grid: the Items range filter no longer fails with "Unknown column item_count". The item count is computed in SQL (item rows, or the menu items JSON when there are none), so filtering and sorting by Items both work and match the count shown in the grid.
