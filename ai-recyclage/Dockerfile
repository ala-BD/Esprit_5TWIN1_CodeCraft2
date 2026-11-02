# syntax=docker/dockerfile:1.7
#
# Micro-service IA du module M4 Recyclage (FastAPI + LightGBM).
#   docker build -t retiss-ai ai-recyclage

FROM python:3.12-slim

LABEL org.opencontainers.image.title="RETISS — IA filière M4"

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    PIP_NO_CACHE_DIR=1 \
    PIP_DISABLE_PIP_VERSION_CHECK=1

# LightGBM a besoin d'OpenMP ; curl sert au healthcheck
RUN apt-get update \
    && apt-get install -y --no-install-recommends libgomp1 curl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /service

COPY requirements.txt .
RUN pip install -r requirements.txt

COPY . .

# Modèle entraîné pendant la construction de l'image : le service démarre tout de suite
# et les tests unitaires valident le moteur avant que l'image ne soit livrée.
RUN python -c "import entrainement; entrainement.entrainer()" \
    && python -m unittest test_moteur -v

RUN useradd --create-home --uid 10001 ia && chown -R ia /service
USER ia

EXPOSE 8001
HEALTHCHECK --interval=20s --timeout=5s --start-period=30s --retries=5 \
    CMD curl -fsS http://127.0.0.1:8001/sante || exit 1

CMD ["uvicorn", "app:app", "--host", "0.0.0.0", "--port", "8001"]
