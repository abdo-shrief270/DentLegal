# ai-svc production image (PROJECT1_PLAN.md §8.3). Models are baked into the image so start-up needs no internet.
FROM python:3.12-slim AS base

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    PIP_NO_CACHE_DIR=1 \
    HF_HOME=/opt/models/hf \
    CAMELTOOLS_DATA=/opt/models/camel

# OCR (S3.3, D6): Tesseract 5 with Arabic and English. tessdata_best can replace the default models later if the
# bake-off shows a gain on scanned case documents.
RUN apt-get update && apt-get install -y --no-install-recommends tesseract-ocr tesseract-ocr-ara tesseract-ocr-eng \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /srv/ai
COPY pyproject.toml ./
COPY app ./app
COPY config ./config
RUN pip install ".[models]"

# Weights: bge-m3 (~2.3 GB), bge-reranker-v2-m3 (~2.3 GB), CAMeL Arabic NER.
RUN python -c "from FlagEmbedding import BGEM3FlagModel, FlagReranker; BGEM3FlagModel('BAAI/bge-m3'); FlagReranker('BAAI/bge-reranker-v2-m3')" \
 && camel_data -i ner-arabert

RUN useradd --system --uid 10001 aisvc && chown -R aisvc /srv/ai
USER aisvc

EXPOSE 8000
HEALTHCHECK --interval=30s --timeout=5s --start-period=180s CMD python -c "import urllib.request; urllib.request.urlopen('http://127.0.0.1:8000/v1/health', timeout=4)"
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000", "--workers", "2"]
