# YouTube Uploads Grid — Plugin Readme

## Description

**YouTube Uploads Grid** is a lightweight WordPress plugin that displays your latest YouTube uploads — including Shorts — using the public RSS feed. It does **not** require an API key. The plugin includes an admin settings page where you can easily enter your YouTube Channel ID or handle.

## Features

- Displays up to **12 recent uploads** (including Shorts)
- Responsive **grid layout** (1–4 columns)
- Built-in **lightbox video player**
- **No API key** required — uses the public YouTube feed
- Simple **shortcode** integration
- Admin **Settings tab** under _Settings → YouTube Uploads Grid_
- Caching for performance (default: 2 hours)

## Installation

1. Upload the plugin folder to `/wp-content/plugins/` or install via the WordPress Plugin Upload tool.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **Settings → YouTube Uploads Grid**.
4. Enter your **YouTube Channel ID** or **handle** (e.g., `UCxxxxxxxxxxxxxxxxxxxxxx` or `@yourhandle`).
5. Click **Save Changes**.

## Usage

### Shortcode

Insert the shortcode anywhere in a post, page, or block editor:

```text
[youtube_uploads]
```

This will automatically display the 12 most recent videos from the channel saved in the settings.

### Optional Attributes

You can override the saved channel or handle directly in the shortcode:

```text
[youtube_uploads channel="UCfcUMdDvjS4Abvy3eVG87pw"]
[youtube_uploads channel="@cupocode"]
```

### Example Usage

```html
<div class="my-youtube-section">
  <h2>Latest Videos</h2>
  [youtube_uploads]
</div>
```

## Settings

Go to **Settings → YouTube Uploads Grid** to manage options:

- **YouTube Channel ID or Handle:** Paste your YouTube channel ID (starts with `UC`) or handle (starts with `@`).
- **Feed URL Preview:** The settings page shows the actual RSS feed URL being used.

## Caching

To reduce load time and API requests, the plugin caches the RSS feed for 2 hours. The cache automatically clears when it expires. If you change your channel, a new cache key is created so new results appear immediately.

## Compatibility

- Requires **WordPress 5.0+**
- Works with **classic and block editors**
- No external dependencies or API keys

## Changelog

### v1.2.0

- Added Settings tab under WordPress Settings menu
- Option to save and manage YouTube Channel ID or handle
- Improved shortcode flexibility and caching per channel
- Maintains full backward compatibility with previous version

## Credits

**Developed by:** Cup O Code
**Website:** [https://cupocode.com](https://cupocode.com)

## License

This plugin is licensed under the **GPL-2.0+ License**.
