"""Local inference; no comments sent to external AI providers."""
from pathlib import Path
import joblib
from text_processing import normalize, has_arabic

ARABIC_LABELS = {"positive": "إيجابي", "negative": "سلبي", "neutral": "محايد"}

class SentimentPredictor:
    def __init__(self, path=None):
        # Load only the trusted model shipped with this project or trained locally.
        path = path or Path(__file__).resolve().parent / "artifacts" / "sentiment.joblib"
        self.model = joblib.load(path)
        self.model_id = self.model["model_id"]

    def predict(self, texts):
        cleaned = [normalize(t) for t in texts]
        vectors = self.model["vectorizer"].transform(cleaned)
        labels = self.model["classifier"].predict(vectors)
        results = []
        for i, text in enumerate(texts):
            if not has_arabic(text):
                results.append({"status": "unsupported", "label": None, "label_ar": "يتطلب تعليقًا عربيًا"})
            elif vectors[i].nnz == 0:
                results.append({"status": "unknown", "label": None, "label_ar": "لا تتوفر كلمات معروفة للنموذج"})
            else:
                label = str(labels[i])
                results.append({"status": "classified", "label": label, "label_ar": ARABIC_LABELS[label]})
        return results
