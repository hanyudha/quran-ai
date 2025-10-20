# Quran AI

Quran AI is a Laravel-based educational platform designed to make the Qur'an more accessible through modern AI technologies. It allows users to **search**, **ask**, and **get tafsir** from Quranic verses (ayah) or chapters (surah), enhanced with semantic search using vector embeddings powered by OpenAI and PostgreSQL with pgvector.

---

## 📑 Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Examples](#examples)
- [Dependencies](#dependencies)
- [Development](#development)
- [Troubleshooting](#troubleshooting)
- [License](#license)
- [Contributors](#contributors)

---

## 🚀 Features

- Search and explore Quranic content with AI assistance
- Generate and store vector embeddings for ayahs using OpenAI
- Use pgvector + PostgreSQL for fast semantic search
- Built with Laravel 12 and modern frontend tools (Vite, Tailwind CSS)
- Queue-driven processing for embedding generation
- Docker-ready (includes setup for pgvector container)

---

## 🛠 Installation

### Prerequisites

- PHP 8.2+
- Composer
- Node.js & NPM
- Docker (for pgvector support)
- PostgreSQL with `pgvector` extension enabled

### Setup Steps

```bash
git clone https://github.com/your-repo/quran-ai.git
cd quran-ai

# PHP & Laravel dependencies
composer install

# Create environment file
cp .env.example .env

# Generate Laravel app key
php artisan key:generate

# Run database migrations
php artisan migrate

# Install front-end dependencies
npm install
npm run build
```

To run everything together:

```bash
composer run dev
```

---

## ⚙️ Configuration

Update the following variables in your `.env` file:

```env
APP_NAME=Quran AI
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=quran_ai
DB_USERNAME=your_pg_user
DB_PASSWORD=your_pg_pass

OPENAI_API_KEY=your_openai_key_here
```

Make sure the `pgvector` extension is enabled in PostgreSQL. If you're using Docker:

```bash
docker build -f pgvector.Dockerfile -t pgvector .
docker run --name pgvector -p 5432:5432 -d pgvector
```

---



---

## 🐳 Docker Setup for PostgreSQL + pgvector

If you don't have a PostgreSQL database with the `pgvector` extension installed, you can spin one up quickly using Docker.

### 🔧 Step-by-Step Setup

1. **Build the Docker image:**

   The project includes a `pgvector.Dockerfile` ready to go.

   ```bash
   docker build -f pgvector.Dockerfile -t pgvector .
   ```

2. **Run the PostgreSQL container:**

   ```bash
   docker run -d \
     --name quran-pgvector \
     -e POSTGRES_DB=quran_ai \
     -e POSTGRES_USER=postgres \
     -e POSTGRES_PASSWORD=postgres \
     -p 5432:5432 \
     pgvector
   ```

3. **Verify pgvector installation:**

   Once the container is running, connect to the database and run:

   ```sql
   SELECT * FROM pg_available_extensions WHERE name = 'vector';
   ```

   If `vector` appears and is `installed`, you're good to go.

4. **Update your `.env` file:**

   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=quran_ai
   DB_USERNAME=postgres
   DB_PASSWORD=postgres
   ```

---

### 📌 Notes

- Port `5432` must be available on your host machine.
- You can inspect logs using:  
  ```bash
  docker logs -f quran-pgvector
  ```
- To stop or remove the container:  
  ```bash
  docker stop quran-pgvector
  docker rm quran-pgvector
  ```


## 📖 Usage

### Generate Embeddings for Ayahs

```bash
php artisan quran:generate-embeddings --limit=100
```

This command uses OpenAI's `text-embedding-3-small` model to generate vector embeddings for Quranic ayahs.

---

### Queue Processing (Optional)

For better performance:

```bash
php artisan queue:work
```

---

## 💡 Examples

#### Search Workflow

1. Generate embeddings for Quranic ayahs.
2. User inputs a query like: `"mercy and forgiveness"`.
3. App performs a vector similarity search against the `embeddings` table.
4. Closest matching ayahs are returned with tafsir (interpretation).

---

## 📦 Dependencies

**Backend:**
- Laravel 12
- PHP 8.2+
- PostgreSQL + pgvector
- openai-php/laravel

**Frontend:**
- Vite
- Tailwind CSS
- Axios

**Dev Tools:**
- Laravel Sail (optional)
- Pint, Pail, PHPUnit
- Docker (for pgvector)

---

## 👨‍💻 Development

Run the full dev environment with:

```bash
composer run dev
```

This will start:
- Laravel server
- Queue listener
- Log tailing (via Pail)
- Vite dev server

---

## 🐛 Troubleshooting

- **No embeddings generated?**  
  Ensure `OPENAI_API_KEY` is set and valid.

- **Database issues?**  
  Confirm PostgreSQL is running and pgvector is installed.

- **Assets not loading?**  
  Run `npm run build` or `npm run dev` again.

---

## 👤 Contributors

- **Wandy Hanyudha** – Creator & Maintainer

---

## 📄 License

This project is licensed under the **MIT License** — you are free to use, modify, and distribute it for educational or personal use.

---

> *Quran AI is built with a purpose to enhance learning and engagement with the Quran through modern technology — may it benefit seekers of knowledge.*
