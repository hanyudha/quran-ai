Write-Host "🔄 Restarting PostgreSQL + pgvector container..." -ForegroundColor Cyan

# Step 1. Stop and remove old container if exists
if ($(docker ps -a -q -f name=pg-quran)) {
    Write-Host "🛑 Stopping old container..." -ForegroundColor Yellow
    docker stop pg-quran | Out-Null
    Write-Host "🧹 Removing old container..." -ForegroundColor Yellow
    docker rm pg-quran | Out-Null
}

# Step 2. Rebuild image from pgvector.Dockerfile
Write-Host "⚙️ Building latest pgvector image..." -ForegroundColor Cyan
docker build -t pgvector-latest -f pgvector.Dockerfile . | Write-Host

# Step 3. Run new container
Write-Host "🚀 Starting new container..." -ForegroundColor Green
docker run -d `
  --name pg-quran `
  -e POSTGRES_USER=postgres `
  -e POSTGRES_PASSWORD=postgres `
  -e POSTGRES_DB=quran_ai `
  -p 5440:5432 `
  pgvector-latest | Write-Host

# Step 4. Show container status
Write-Host "`n[INFO] Running containers:" -ForegroundColor Cyan
