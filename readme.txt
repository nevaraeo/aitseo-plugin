=== Aitseo Connect ===
Contributors: aitseo
Tags: seo, content, automation, publishing, ai
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect your WordPress site to Aitseo for AI-powered SEO article publishing.

== Description ==

Aitseo Connect bridges your WordPress site with the [Aitseo](https://aitseo.com) AI SEO writing platform, enabling one-click automatic article publishing.

**Features:**

* **One-click connection** — Secure key-based authentication, no passwords shared
* **Automatic publishing** — Articles generated on Aitseo are published directly to WordPress
* **SEO meta support** — Automatically sets meta description, SEO title, and focus keyword
* **Yoast SEO & RankMath compatible** — Writes to both plugins' meta fields
* **Schema JSON-LD** — Structured data markup injected in `<head>` for rich search results
* **Category & tag mapping** — Assign categories and tags during publishing
* **Featured image support** — Automatically downloads and sets featured images
* **IndexNow integration** — Pings search engines (Google, Bing) when new content is published
* **Scheduled publishing** — Supports future-dated posts

**How it works:**

1. Install and activate this plugin on your WordPress site
2. Copy the Connection Key from Settings → Aitseo Connect
3. Paste it in your Aitseo dashboard under Platform Integration → WordPress
4. Articles you generate on Aitseo can now be published to your site with one click

**External services:**

This plugin connects to the following external services:

* **Aitseo** ([aitseo.com](https://aitseo.com)) — Receives publishing requests from the Aitseo platform via REST API. [Privacy Policy](https://aitseo.com/privacy) | [Terms of Service](https://aitseo.com/terms)
* **IndexNow API** ([api.indexnow.org](https://api.indexnow.org)) — Notifies search engines about new content for faster indexing. [IndexNow documentation](https://www.indexnow.org/)
* **Google Ping** — Submits sitemap URL to Google for crawling
* **Bing Ping** — Submits sitemap URL to Bing for crawling

== Installation ==

1. Upload the `aitseo-connect` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings → Aitseo Connect
4. Copy the Connection Key displayed on the settings page
5. Log in to [aitseo.com](https://aitseo.com), go to Platform Integration → WordPress
6. Paste the Connection Key and your site URL, then click Connect

== Frequently Asked Questions ==

= Is an Aitseo account required? =

Yes. This plugin connects your WordPress site to the Aitseo platform. You need an account at [aitseo.com](https://aitseo.com) to generate and publish articles.

= Is my site data sent to Aitseo? =

No. This plugin only receives data from Aitseo (article content to publish). It does not send your site content, user data, or any private information to external servers. The only outbound requests are search engine pings (IndexNow, Google, Bing) when new articles are published.

= Does it work with Yoast SEO and RankMath? =

Yes. The plugin automatically writes SEO meta fields (title, description, focus keyword) compatible with both Yoast SEO and RankMath.

= Can I disable the connection temporarily? =

Yes. Go to Settings → Aitseo Connect and uncheck "Allow Aitseo to publish articles to this site". You can re-enable it at any time.

= How do I regenerate the Connection Key? =

Go to Settings → Aitseo Connect and click "Regenerate Connection Key". The old key will immediately stop working.

== Screenshots ==

1. Settings page with Connection Key
2. One-click copy for easy setup

== Changelog ==

= 1.0.0 =
* Initial release
* Secure key-based authentication
* Article publishing via REST API
* Yoast SEO and RankMath meta field support
* Schema JSON-LD markup injection
* IndexNow search engine notification
* Featured image support
* Category and tag assignment

== Upgrade Notice ==

= 1.0.0 =
Initial release. Install and connect your site to Aitseo for AI-powered article publishing.
