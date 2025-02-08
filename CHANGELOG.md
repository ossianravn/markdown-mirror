# Changelog

All notable changes to the Markdown Mirror plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.2.0] - 2024-03-21

### Added
- Support for Markdown versions of taxonomy archive pages
- Category and tag archives as optional content types in settings
- Markdown generation for taxonomy archives
- SEO headers and alternate links for taxonomy pages
- Customizable context generation through admin settings
- Expanded context files (llms-ctx.txt and llms-ctx-full.txt)
- Automated context updates through caching system

### Changed
- Updated rewrite rules to handle taxonomy URLs
- Enhanced admin settings interface for taxonomy support
- Improved context file generation and caching

## [0.1.0] - 2024-03-20

### Added
- Initial beta release with core functionality
- Dynamic Markdown conversion and context file generation
- Support for three context file types: llms.txt, llms-ctx.txt, and llms-ctx-full.txt
- Category and tag support in context files
- Per-post control via meta boxes
- Caching system for improved performance
- SEO enhancements with canonical and alternate links
- Comprehensive test suite
- Admin settings for controlling context file content and behavior

### Changed
- Removed `.md` disallow rule from robots.txt
- Enhanced SEO handling with bidirectional linking

### Fixed
- Trailing slash handling in rewrite rules
- Initial repository setup and documentation
