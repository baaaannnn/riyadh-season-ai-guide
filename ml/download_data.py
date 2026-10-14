"""Download the original ASTD release; raw data is not included in the deliverable."""
from pathlib import Path
import hashlib
import urllib.request

COMMIT = "0eac69a559bbc38a2cf06266411fa8bf47eed042"
URL = f"https://raw.githubusercontent.com/mahmoudnabil/ASTD/{COMMIT}/data/Tweets.txt"
DESTINATION = Path(__file__).resolve().parent / "data" / "Tweets.txt"

if __name__ == "__main__":
    with urllib.request.urlopen(URL, timeout=60) as response:
        content = response.read()
    # Canonical newline ensures the same input hash across transfer routes.
    content = (content.decode("utf-8").replace("\r\n", "\n").replace("\r", "\n").rstrip("\n") + "\n").encode("utf-8")
    digest = hashlib.sha256(content).hexdigest()
    expected = "616de26a4e420d98611e16a2c4b61be6b83fd6e6951a8a4985da565871506e04"
    if digest != expected:
        raise SystemExit("Dataset checksum mismatch; file not written.")
    DESTINATION.parent.mkdir(parents=True, exist_ok=True)
    DESTINATION.write_bytes(content)
    print("ASTD saved:", DESTINATION, "SHA256:", digest)
