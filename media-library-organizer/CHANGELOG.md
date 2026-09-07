#####   Version 2.1.3 (2026-09-07)

- Fixed third-party media-folder imports timing out or stopping partway through by processing large migrations in resumable background batches with progress reporting.
- Fixed stale image copies after you replace media files.
- Fixed blank Camera filters in the Media Library list view.
- Fixed blank Media Categories filters in the Media Library list view.

#####   Version 2.1.2 (2026-07-23)

- Fixed the plugins filters not displaying correctly in the media modal on WordPress 7.0 and newer.
- Fixed an issue where Editor users could not manage media folders because administrator-level permissions were required.
- Updated dependencies

#####   Version 2.1.1 (2026-05-15)

- Updated dependencies

####   Version 2.1.0 (2026-03-23)

### New Features

- Added MLO Gallery block for displaying dynamic image galleries from media categories and folders, with grid, masonry, carousel, and justified layouts.
- Added Replace Media functionality to swap files while preserving all metadata, categories, and references across your site.
- Added drag-and-drop folder reordering to arrange folders in any custom order.
- Added double-click to rename folders directly in the sidebar.
- Added media upload status panel showing real-time progress, success, and error states during file uploads.
- Added import support for Media Library Assistant, Mediamatic, and WP Real Media Library.

### Enhancements

- Redesigned the sidebar UI to match your WordPress color scheme for a more native look and feel.
- Added Move option to the folder context menu for easier folder reorganization.
- Improved drag-and-drop with better hierarchy validation, cross-parent moves, and root-level drop zone.
- Improved error messages and UI strings across the plugin for clarity and consistency.

### Bug Fixes

- Fixed folders not being created under the selected parent folder.
- Fixed drag issue when reordering items in grid mode.
- Fixed media upload panel stacking order (z-index) to prevent overlap with other elements.
- Fixed compatibility with custom columns in the media library list view.
- Fixed handling of empty columns during output module installation.
- Fixed undefined array key warnings.
- Fixed error with EXIF and IPTC fields not saving data.

#####   Version 2.0.4 (2025-12-17)

- Fixed issue with creating new Media Category from “Attachment details” modal
- Improved loading in grid view with lazy load
- Fixing styling issues with banner and notice

#####   Version 2.0.3 (2025-12-03)

- Fixed file upload inside category
- Fixed category state in media grid view
- Fixed compatibility with some plugins that was caused by a class name
- Added NPS survey

#####   Version 2.0.2 (2025-10-20)

- Fixed search for subfolders

#####   Version 2.0.1 (2025-10-13)

* Fix build by adding missing js files

####   Version 2.0.0 (2025-10-13)

### New Features

- Added the ability to download Media Library folders.
- Introduced an option to set a default startup folder in the Media Library.
- Added Show file count and Show empty folder options for better folder management.
- [PRO] Introduced sortable Media Categories within folders, making media organization faster and more intuitive.

### Enhancements

- Simplified the Plugin Settings UI for a cleaner, more user-friendly experience.
- Introduced a modern, redesigned sidebar for media categories, featuring new management options.
- Defaults and Output module options are now accessible in the free version.
- Optimized dynamic tag generation for better linking between fields and documentation references.
- Improved performance and load times for downloading and managing large media folders.
- Updated the license system to support automated activation workflows.

### Bug Fixes

- Fixed Load Textdomain translation errors in both free and pro versions.
- Resolved several UI layout inconsistencies across screens.
- Fixed PHP 8.3 compatibility issues to ensure smooth operation on newer server environments.
- Resolved pagination display issues in the Dynamic Gallery shortcode.
- Fixed an issue where Media Category items did not update correctly in Quick Edit mode.
- Corrected category assignment errors when uploading images through the page editor.
- Fixed auto-categorization upload errors triggered during media uploads.

#####   Version 1.6.5 (2024-08-03)

- Fixed the issue that was not allowing to active license key for the PRO plugin

#####   Version 1.6.4 (2024-07-01)

* Improve translation compatibility

#####   Version 1.6.3 (2024-06-29)

- Fix broken version tag
