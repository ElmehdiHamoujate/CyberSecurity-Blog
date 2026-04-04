# SMB CyberSecurity Blog

An automated cybersecurity blog for small and medium businesses. Claude generates one post per day (every 5 minutes in test mode), including Amazon affiliate product recommendations tailored to each post's topic.

**Stack:** PHP 8.3 · SQLite · Claude API · Railway

---

## Project Structure

```
CyberSecurity-Blog/
├── public/
│   ├── index.php          # Post listing (paginated)
│   ├── post.php           # Single post view
│   └── assets/
│       └── style.css
├── src/
│   ├── Database.php       # SQLite wrapper + auto-migration
│   └── BlogGenerator.php  # Claude API client
├── cron/
│   └── generate_post.php  # Post generation script
├── railway.toml           # Web service config
├── nixpacks.toml          # PHP + extensions
└── .env.example
```

---

## Railway Deployment

### Step 1 — Create a new Railway project

1. Go to [railway.com](https://railway.com) → **New Project** → **Deploy from GitHub repo**
2. Connect your GitHub account and select this repository.
3. Railway will auto-detect the `railway.toml` and start the web service.

### Step 2 — Add a persistent Volume for SQLite

SQLite data must live on a Volume or it will be wiped on every deploy.

1. In the Railway project, click your **web service** → **Volumes** tab.
2. Click **+ Add Volume**.
3. Set **Mount Path** to `/data`.
4. Click **Add**.

### Step 3 — Set environment variables

In the web service → **Variables** tab, add:

| Variable             | Value                          |
|----------------------|-------------------------------|
| `CLAUDE_API_KEY`     | Your Anthropic API key        |
| `AMAZON_ASSOCIATE_ID`| `270cc89-20`                  |
| `DB_PATH`            | `/data/blog.db`               |
| `POST_INTERVAL`      | `300` (5 min) or `86400` (1 day) |

### Step 4 — Add the Cron service

The cron service runs `generate_post.php` on a schedule.

1. In your Railway project → **+ New Service** → **GitHub Repo** (same repo).
2. In the new service settings → **Settings** → **Start Command**:
   ```
   php cron/generate_post.php
   ```
3. Go to **Settings** → find **Cron Schedule** and set:
   - Testing: `*/5 * * * *` (every 5 minutes)
   - Production: `0 8 * * *` (8 AM daily)
4. Add the **same environment variables** as the web service (Step 3).
5. Attach the **same Volume** (`/data`) to this service so it shares the database.

### Step 5 — Deploy

Push to your connected GitHub branch. Railway will build and deploy automatically.

---

## Local Development

1. Copy `.env.example` to `.env` and fill in your keys.
2. Make sure PHP 8.1+ is installed with `pdo_sqlite` and `curl` extensions.
3. Start the PHP dev server:
   ```bash
   php -S localhost:8080 -t public/
   ```
4. To generate a test post manually:
   ```bash
   php cron/generate_post.php
   ```
5. Open [http://localhost:8080](http://localhost:8080) in your browser.

---

## How It Works

1. The **Cron service** on Railway runs `generate_post.php` on schedule.
2. The script checks when the last post was created. If the interval hasn't passed, it exits silently.
3. It calls **Claude API** with a prompt requesting:
   - A unique cybersecurity topic for SMBs
   - Full blog post HTML (~650 words)
   - 3 Amazon product/book recommendations with search queries
4. The post and affiliate HTML are saved to **SQLite**.
5. The **web service** reads from SQLite and serves the blog.

---

## Security Notice

- Never commit your `.env` file or API keys to git.
- Always set secrets via Railway's **Variables** tab, not in config files.
- Rotate the Claude API key at [console.anthropic.com](https://console.anthropic.com) if it has ever appeared in plain text.
