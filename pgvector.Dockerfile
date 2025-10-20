FROM postgres:17

# Install build tools + PostgreSQL dev headers + build pgvector from source
RUN apt-get update && apt-get install -y \
    git \
    build-essential \
    postgresql-server-dev-17 \
 && git clone https://github.com/pgvector/pgvector.git \
 && cd pgvector && make && make install \
 && cd .. && rm -rf pgvector \
 && apt-get remove -y git build-essential postgresql-server-dev-17 && apt-get autoremove -y \
 && rm -rf /var/lib/apt/lists/*

ENV POSTGRES_USER=postgres
ENV POSTGRES_PASSWORD=postgres
ENV POSTGRES_DB=quran_ai
