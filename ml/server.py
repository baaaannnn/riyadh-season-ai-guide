"""Small localhost-only JSON service for the PHP site."""
import argparse
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
import json
from urllib.parse import urlsplit
from predictor import SentimentPredictor

MAX_TEXT_LENGTH = 2000
MAX_BATCH = 100
MAX_BODY = 1_000_000

def build_handler(predictor):
    class Handler(BaseHTTPRequestHandler):
        server_version = "RiyadhSentiment/1.0"

        def log_message(self, fmt, *args):
            # Do not record review texts or personal details.
            pass

        def respond(self, status, payload):
            content = json.dumps(payload, ensure_ascii=False).encode("utf-8")
            self.send_response(status)
            self.send_header("Content-Type", "application/json; charset=utf-8")
            self.send_header("Cache-Control", "no-store")
            self.send_header("Content-Length", str(len(content)))
            self.end_headers()
            self.wfile.write(content)

        def do_GET(self):
            if urlsplit(self.path).path != "/health":
                return self.respond(404, {"error": "not_found"})
            self.respond(200, {"status": "ok", "model_id": predictor.model_id,
                               "labels": ["negative", "neutral", "positive"]})

        def do_POST(self):
            if urlsplit(self.path).path != "/predict":
                return self.respond(404, {"error": "not_found"})
            if self.headers.get("Content-Type", "").split(";")[0].strip() != "application/json":
                return self.respond(415, {"error": "json_required"})
            try:
                length = int(self.headers.get("Content-Length", "0"))
                if not 0 < length <= MAX_BODY:
                    return self.respond(413, {"error": "invalid_body_size"})
                self.connection.settimeout(10)
                payload = json.loads(self.rfile.read(length).decode("utf-8"))
                texts = payload.get("texts") if isinstance(payload, dict) else None
                if not isinstance(texts, list) or not 1 <= len(texts) <= MAX_BATCH:
                    return self.respond(400, {"error": "texts_must_be_a_nonempty_list_of_at_most_100"})
                if any(not isinstance(t, str) or not t.strip() or len(t) > MAX_TEXT_LENGTH for t in texts):
                    return self.respond(400, {"error": "each_text_must_have_1_to_2000_characters"})
                self.respond(200, {"model_id": predictor.model_id, "results": predictor.predict(texts)})
            except (ValueError, UnicodeError):
                self.respond(400, {"error": "invalid_json"})
            except Exception:
                self.respond(503, {"error": "prediction_unavailable"})
    return Handler

if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--port", type=int, default=5050)
    args = parser.parse_args()
    predictor = SentimentPredictor()
    server = ThreadingHTTPServer(("127.0.0.1", args.port), build_handler(predictor))
    print(f"Arabic sentiment service: http://127.0.0.1:{args.port} | {predictor.model_id}", flush=True)
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        server.server_close()
