import json
from pathlib import Path
import sys
import threading
import unittest
import urllib.error
import urllib.request
from http.server import ThreadingHTTPServer

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / 'ml'))
from predictor import SentimentPredictor
from server import build_handler
from text_processing import normalize

class MLTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.predictor = SentimentPredictor()
        cls.server = ThreadingHTTPServer(('127.0.0.1', 0), build_handler(cls.predictor))
        cls.thread = threading.Thread(target=cls.server.serve_forever, daemon=True)
        cls.thread.start()
        cls.base = f'http://127.0.0.1:{cls.server.server_port}'

    @classmethod
    def tearDownClass(cls):
        cls.server.shutdown()
        cls.server.server_close()
        cls.thread.join()

    def post(self, payload, content_type='application/json'):
        body = payload if isinstance(payload, bytes) else json.dumps(payload).encode()
        req = urllib.request.Request(self.base+'/predict', data=body, headers={'Content-Type':content_type})
        try:
            with urllib.request.urlopen(req, timeout=10) as response:
                return response.status, json.load(response)
        except urllib.error.HTTPError as error:
            return error.code, json.load(error)

    def test_normalization_preserves_negation(self):
        self.assertIn('ليس', normalize('المكان لَيْسَ جميلاً'))
        self.assertEqual(normalize('أحبّ الرياض'), normalize('احب الرياض'))

    def test_remove_identifiers(self):
        cleaned = normalize('رائع @someone https://example.com user@example.com 0551234567')
        self.assertNotIn('someone', cleaned)
        self.assertNotIn('example', cleaned)
        self.assertNotIn('055', cleaned)

    def test_non_arabic_is_not_neutral(self):
        result = self.predictor.predict(['great place'])[0]
        self.assertEqual(result['status'], 'unsupported')
        self.assertIsNone(result['label'])

    def test_demo_predictions(self):
        texts = ['تجربة رائعة وممتعة شكرا لكم', 'خدمة سيئة وتجربة فاشلة ومخيبة للآمال', 'تقام الفعالية يوم الخميس في الرياض']
        self.assertEqual([r['label'] for r in self.predictor.predict(texts)], ['positive','negative','neutral'])

    def test_real_api_batch(self):
        code, result = self.post({'texts':['تجربة رائعة وممتعة شكرا لكم','great']})
        self.assertEqual(code, 200)
        self.assertEqual(len(result['results']), 2)
        self.assertEqual(result['model_id'], self.predictor.model_id)
        self.assertEqual(result['results'][1]['status'], 'unsupported')

    def test_reject_bad_requests(self):
        for payload in [{'texts':[]}, {'texts':['']}, {'texts':[1]}, {'texts':['ا'*2001]}, {'texts':['رائع']*101}, [], b'{broken']:
            self.assertEqual(self.post(payload)[0], 400)
        self.assertEqual(self.post({'texts':['رائع']}, 'text/plain')[0], 415)

    def test_health(self):
        with urllib.request.urlopen(self.base+'/health') as response:
            self.assertEqual(json.load(response)['status'], 'ok')

    def test_disjoint_split_hashes(self):
        manifest = json.loads((ROOT/'ml/reports/split_manifest.json').read_text())
        sets = {k:{x['text_sha256'] for x in v} for k,v in manifest.items()}
        self.assertFalse(sets['train'] & sets['test'])
        self.assertFalse(sets['train'] & sets['validation'])
        self.assertFalse(sets['test'] & sets['validation'])

    def test_selected_on_validation(self):
        metrics=json.loads((ROOT/'ml/reports/metrics.json').read_text())
        best=max(['ComplementNB','LinearSVC'],key=lambda k:metrics['validation'][k]['macro_f1'])
        self.assertEqual(metrics['selected_model'],best)

if __name__ == '__main__':
    unittest.main(verbosity=2)
