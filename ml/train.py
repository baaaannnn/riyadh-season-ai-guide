"""Reproducible three-class ASTD experiment. Model selection uses validation only."""
import argparse
from collections import Counter, defaultdict
import hashlib
import json
from pathlib import Path
import platform

import joblib
import numpy as np
import sklearn
from sklearn.dummy import DummyClassifier
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.pipeline import FeatureUnion
from sklearn.model_selection import train_test_split
from sklearn.naive_bayes import ComplementNB
from sklearn.svm import LinearSVC
from sklearn.metrics import accuracy_score, f1_score, classification_report, confusion_matrix
from sklearn.utils.class_weight import compute_sample_weight
from text_processing import normalize, has_arabic

ROOT = Path(__file__).resolve().parent
LABELS = ["negative", "neutral", "positive"]
MAPPING = {"NEG": "negative", "OBJ": "neutral", "POS": "positive"}
SOURCE_COMMIT = "0eac69a559bbc38a2cf06266411fa8bf47eed042"

def load_data(path):
    grouped = defaultdict(set)
    raw_counts = Counter()
    for line in Path(path).read_text(encoding="utf-8").splitlines():
        if not line.strip():
            continue
        text, label = line.rsplit("\t", 1)
        raw_counts[label] += 1
        if label not in MAPPING:  # ASTD NEUTRAL is subjective mixed, not objective.
            continue
        text = normalize(text)
        if has_arabic(text):
            grouped[text].add(MAPPING[label])
    clean = sorted((text, next(iter(labels))) for text, labels in grouped.items() if len(labels) == 1)
    return ([x[0] for x in clean], np.array([x[1] for x in clean]), {
        "raw_label_counts": dict(raw_counts), "retained": len(clean),
        "conflicting_normalized_texts_removed": sum(len(v) > 1 for v in grouped.values()),
        "label_mapping": MAPPING, "excluded": "NEUTRAL (subjective mixed)",
    })

def make_features():
    # Fit on the training partition only. Negation words are kept.
    return FeatureUnion([
        ("word", TfidfVectorizer(ngram_range=(1, 2), min_df=2, max_features=45000, sublinear_tf=True)),
        ("char", TfidfVectorizer(analyzer="char_wb", ngram_range=(3, 5), min_df=3,
                                max_features=65000, sublinear_tf=True)),
    ])

def make_classifier(name):
    if name == "LinearSVC":
        return LinearSVC(C=1.0, class_weight="balanced", random_state=42, max_iter=6000)
    if name == "ComplementNB":
        return ComplementNB(alpha=0.5)
    return DummyClassifier(strategy="most_frequent")

def fit_classifier(name, x, y):
    classifier = make_classifier(name)
    if name == "ComplementNB":
        classifier.fit(x, y, sample_weight=compute_sample_weight("balanced", y))
    else:
        classifier.fit(x, y)
    return classifier

def metrics(y, pred):
    return {"accuracy": float(accuracy_score(y, pred)),
            "macro_f1": float(f1_score(y, pred, labels=LABELS, average="macro", zero_division=0)),
            "classification_report": classification_report(y, pred, labels=LABELS, output_dict=True, zero_division=0),
            "confusion_matrix": confusion_matrix(y, pred, labels=LABELS).tolist()}

def run_training(data_path, output_root=ROOT):
    output_root = Path(output_root)
    artifacts = output_root / "artifacts"
    reports = output_root / "reports"
    artifacts.mkdir(parents=True, exist_ok=True)
    reports.mkdir(parents=True, exist_ok=True)
    texts, labels, cleaning = load_data(data_path)
    indexes = np.arange(len(texts))
    development, test = train_test_split(indexes, test_size=0.15, stratify=labels, random_state=42)
    train, validation = train_test_split(development, test_size=0.15 / 0.85,
                                        stratify=labels[development], random_state=42)
    text_sets = {name: set(texts[i] for i in ids) for name, ids in
                 [("train", train), ("validation", validation), ("test", test)]}
    assert not text_sets["train"] & text_sets["test"]
    assert not text_sets["train"] & text_sets["validation"]
    assert not text_sets["validation"] & text_sets["test"]
    vectorizer = make_features()
    x_train = vectorizer.fit_transform([texts[i] for i in train])
    x_valid = vectorizer.transform([texts[i] for i in validation])
    validation_results = {}
    names = ["ComplementNB", "LinearSVC", "MajorityBaseline"]
    for name in names:
        clf = fit_classifier(name, x_train, labels[train])
        validation_results[name] = metrics(labels[validation], clf.predict(x_valid))
        print(name, "validation macro-F1", round(validation_results[name]["macro_f1"], 4), flush=True)
    selected = max(names[:2], key=lambda n: validation_results[n]["macro_f1"])
    # Selection is frozen BEFORE looking at held-out test metrics.
    final_features = make_features()
    x_dev = final_features.fit_transform([texts[i] for i in development])
    x_test = final_features.transform([texts[i] for i in test])
    test_results = {}
    trained = {}
    for name in names:
        clf = fit_classifier(name, x_dev, labels[development])
        trained[name] = clf
        test_results[name] = metrics(labels[test], clf.predict(x_test))
        print(name, "test", round(test_results[name]["accuracy"], 4), round(test_results[name]["macro_f1"], 4), flush=True)
    raw_hash = hashlib.sha256(Path(data_path).read_bytes()).hexdigest()
    model_id = f"astd-{selected.lower()}-{raw_hash[:10]}-seed42-v1"
    model = {"vectorizer": final_features, "classifier": trained[selected], "labels": LABELS,
             "model_id": model_id, "normalizer_version": 1}
    joblib.dump(model, artifacts / "sentiment.joblib", compress=3)
    report = {
        "dataset": "ASTD", "source_commit": SOURCE_COMMIT, "source_sha256": raw_hash,
        "seed": 42, "cleaning": cleaning, "split": {}, "labels_order": LABELS,
        "selection_rule": "Highest validation macro-F1; test not used for selection",
        "selected_model": selected, "model_id": model_id, "validation": validation_results,
        "test": test_results, "normalized_text_overlap": 0,
        "environment": {"python": platform.python_version(), "sklearn": sklearn.__version__,
                        "numpy": np.__version__, "joblib": joblib.__version__},
    }
    manifest = {}
    for name, ids in [("train", train), ("validation", validation), ("test", test)]:
        report["split"][name] = {"size": len(ids), "counts": dict(Counter(labels[ids]))}
        manifest[name] = [{"text_sha256": hashlib.sha256(texts[i].encode()).hexdigest(),
                           "label": str(labels[i])} for i in ids]
    (reports / "metrics.json").write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")
    (reports / "split_manifest.json").write_text(json.dumps(manifest, indent=2), encoding="utf-8")
    np.savez_compressed(reports / "test_predictions.npz", y_true=labels[test],
                        **{name: trained[name].predict(x_test) for name in names})
    import matplotlib
    matplotlib.use("Agg")
    import matplotlib.pyplot as plt
    from sklearn.metrics import ConfusionMatrixDisplay
    fig, ax = plt.subplots(figsize=(6.4, 5.4))
    ConfusionMatrixDisplay(np.array(test_results[selected]["confusion_matrix"]), display_labels=LABELS).plot(ax=ax, cmap="Blues", colorbar=False)
    ax.set_title(f"{selected} | Held-out ASTD test set")
    fig.tight_layout()
    fig.savefig(reports / "confusion_matrix.png", dpi=160)
    plt.close(fig)
    print("Selected:", selected, "Model:", model_id, flush=True)
    return report

if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--data", type=Path, default=ROOT / "data" / "Tweets.txt")
    args = parser.parse_args()
    if not args.data.exists():
        parser.error("Dataset missing. Run python ml/download_data.py first.")
    run_training(args.data)
