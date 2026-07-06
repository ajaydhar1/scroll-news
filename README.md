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

## 🔒 Security

- Local development credentials are stored in `core/config/local.php`.
- A template is provided in `core/config/local.example.php`.
- `local.php` is excluded from version control.
- Production uses the `DATABASE_URL` environment variable.

## Getting Started

1. Clone the repository.
2. Copy `core/config/local.example.php` to `core/config/local.php`.
3. Add your local `DATABASE_URL`.
4. Install dependencies.
5. Run the application locally.

## 📸 Demo

![Scroll News Demo](demo-image.png)  
*A modern take on staying informed.*

---

Built and maintained by Ajay Dhar.
