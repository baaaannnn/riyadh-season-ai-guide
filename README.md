# Riyadh Season AI Guide

An academic web application for exploring Riyadh Season events, with Arabic sentiment classification integrated into visitor comments on each event page.

## Features
- Browse and search events.
- Rate events, write reviews, and like visitor reviews.
- Display positive comments in green, negative comments in red, and neutral comments in grey.
- Manage events through an administrator interface.

## Technologies
PHP, HTML, CSS, JavaScript, MySQL/MariaDB, Python, scikit-learn, and TF-IDF feature extraction. The sentiment workflow compares LinearSVC and Complement Naive Bayes classifiers.

## Architecture
The PHP application communicates with a local Python sentiment service through JSON. Review text is preprocessed and classified before the sentiment label is displayed within the event page.

## Repository Status
Project documentation is published. Source-code upload is still pending; this repository is not yet a complete runnable checkout.

Academic team project; this portfolio does not claim sole authorship.
