# 🧠 Scroll News – Real-Time News Analytics Dashboard

**Scroll News** is a smart, U.S.-focused news analytics website that breaks down the latest trending news stories into their core insights using AI and natural language processing (NLP). Built for people who want to quickly grasp what’s happening in the world — and experience the joy of stumbling upon meaningful stories.

## 🚀 Features

- 🔄 **Stumble Through the News**  
  Just like the old *StumbleUpon* experience, every visit gives you a fresh, randomly selected trending article with its NLP analysis — one click to stumble, scroll, and discover what's happening now.

- 📡 **Live Feed of Trending News**  
  Pulls in stories from top national and local sources, always current and relevant.

- 🧠 **NLP-Powered Analytics**  
  Breaks each article into key people, places, keywords, emotional tones, and more.

- 📰 **Side-by-Side Dashboard**  
  Split-screen layout with the article on the right and the analysis on the left — glance or go deep.

- 🖼️ **Visual Article Screenshots**  
  Articles are rendered visually for fast scanning and full context.

- 🧭 **Depth Chart**  
  The depth chart surfaces and organizes the subject layers of the article into distinct dimensions, giving you a clear, structured view of what’s woven into the story beneath the surface.

- 🔗 **Sharable URLs**  
  Each analysis is bookmarkable and linkable with a query parameter for the article URL.

- 🕰️ **Coverage Timelines**  
  Curated, chronological timelines for developing stories (`/timeline`), built from archived articles with confirmed metadata kept separate from editorial chapters. Stories are defined in `core/timelines/stories/`; run `php tools/timeline_audit.php` to validate them against the database (read-only).

- 👤 **Authentication System**  
  User accounts with authentication, profiles, reading history, saved articles, and personalized News Trails.


## 🌎 Audience

This site was designed for people living in the **United States**, or for anyone interested in **U.S. news and politics**. We aim to provide clean, digestible insights so you can understand the heart of a story in seconds.

## 📊 Analytics You Can Trust

We don't just summarize — we **distill**. Our analytics include:

- 🔍 Named entities (people, places, organizations)
- 🗂️ Narrative frames and keyword breakdowns
- 💬 Sentiment and emotional reaction
- 📚 Wikipedia links to deepen context
- 🧩 Topic extraction
- 📖 Reading history
- 🔎 Search history
- 🛤️ News Trails
- 🎯 Personalized recommendations
- 🤖 AI analysis pipeline

## 📦 Tech Stack

- 🐍 Python Flask API (NLP processing, screenshots)
- 📰 PHP front-end (Bootstrap, Intro.js, Open Graph parsing)
- 🤖 NLP Libraries: spaCy, NLTK, HuggingFace Transformers (custom blend)
- 🌐 Playwright for full-page article screenshots
- 🐘 PostgreSQL
- 🎨 Bootstrap

## Security

Local development credentials are stored in:

core/config/local.php

A template is provided in:

core/config/local.example.php

The local configuration file is excluded from version control.

## Getting Started

1. Clone the repository.
2. Copy `core/config/local.example.php` to `core/config/local.php`.
3. Add your local `DATABASE_URL`.
4. Install dependencies.
5. Run the application locally.

## Install and Test the PWA

The site can be installed over HTTPS. `localhost` and loopback addresses are also considered secure for development on the same device; for testing on a phone, use a reachable HTTPS host because the phone's `localhost` refers to the phone itself. The service worker caches only same-origin CSS and JavaScript files under `/assets/css/` and `/assets/js/` when their URL has exactly one numeric `v` version parameter, matching the existing PHP asset URLs. It does not cache pages, API responses, account/authentication routes, feeds, article data, or offline article content.

### iOS Safari

1. Open the Scroll News site in Safari on an iPhone or iPad.
2. Use **Share** and choose **Add to Home Screen**, then confirm the name and add it.
3. Launch Scroll News from the Home Screen. Confirm the page loads and the theme control, navigation, and sign-in flow behave as they do in Safari.
4. In Safari Web Inspector, inspect the page's service worker and Cache Storage. Confirm only versioned local CSS/JavaScript assets appear. Web Push is not part of this phase.

### Android Chrome

1. Open the Scroll News site in Chrome on an Android device.
2. Use the browser menu's **Install app** or **Add to Home screen** action and confirm installation.
3. Launch the installed app. Confirm navigation, responsive layouts, theme selection, sign-in, and saved-headline behavior.
4. In Chrome DevTools, inspect **Application > Manifest**, **Service Workers**, and **Cache Storage**. Confirm the manifest icons load and only versioned local CSS/JavaScript assets are cached.

### Browser and privacy checks

Test in a normal browser tab as well as the installed app. Sign in, open account pages, save a headline, and inspect Network and Cache Storage: PHP page navigations and API responses must remain network requests and must not appear in persistent caches. Repeat with a signed-out session, test light and dark themes, and verify responsive behavior. Offline article/page access is intentionally unsupported; only previously requested versioned static CSS/JavaScript can be served from cache.

## 📸 Demo

![Scroll News Demo](demo-image.png)  
*A modern take on staying informed.*

---

Built and maintained by Ajay Dhar.
