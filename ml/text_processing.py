"""Shared Arabic normalization for training and inference; preserves negation."""
import re
import unicodedata

ARABIC = re.compile(r"[\u0621-\u064a\u066e-\u06d3]")

def normalize(text):
    text = unicodedata.normalize("NFKC", text).lower()
    text = re.sub(r"https?://\S+|www\.\S+|@[\w_]+|[\w.+-]+@[\w.-]+\.[a-z]+", " ", text)
    text = re.sub(r"[\u0610-\u061a\u064b-\u065f\u0670\u06d6-\u06ed\u0640]", "", text)
    text = text.translate(str.maketrans("أإآٱى", "ااااي"))
    text = re.sub(r"(.)\1{2,}", r"\1\1", text)
    text = re.sub(r"[^\w\s!?\u0600-\u06ff]", " ", text)
    text = re.sub(r"\d+", " ", text)
    return " ".join(text.split())

def has_arabic(text):
    return bool(ARABIC.search(normalize(text)))
