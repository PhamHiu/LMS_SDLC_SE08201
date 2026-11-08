"""
Smart LMS - Student Dropout Prediction Model Training Pipeline
Trains Baseline (Logistic Regression) vs Candidate (Random Forest) models.
Evaluates using Precision, Recall, F1, ROC-AUC, and Confusion Matrix.
Complies with BestCoder 2026 Master Prompt Section X (10.4, 10.5, 10.6, 10.7).
"""

import os
import json
import joblib
import numpy as np
import pandas as pd
from datetime import datetime

from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler
from sklearn.linear_model import LogisticRegression
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import (
    accuracy_score,
    precision_score,
    recall_score,
    f1_score,
    roc_auc_score,
    confusion_matrix,
    classification_report
)

from dataset import generate_synthetic_dataset, FEATURE_COLUMNS


def train_models(data_path: str = None, artifact_dir: str = '.'):
    print("=" * 60)
    print(" SMART LMS - ML TRAINING PIPELINE (BestCoder 2026)")
    print("=" * 60)

    # 1. Load or Generate Dataset
    if data_path and os.path.exists(data_path):
        print(f"[1/6] Loading data from {data_path}...")
        df = pd.read_csv(data_path)
    else:
        print("[1/6] Generating synthetic dataset (1,500 samples)...")
        df = generate_synthetic_dataset(num_samples=1500, random_seed=2026)

    print(f"      Total records: {len(df)}")
    print(f"      Dropout positive rate: {df['dropout'].mean() * 100:.2f}%")

    X = df[FEATURE_COLUMNS]
    y = df['dropout']

    # 2. Train/Test Split (Stratified 80/20)
    print("[2/6] Splitting dataset (80% Train, 20% Test)...")
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.20, random_state=42, stratify=y
    )

    # 3. Standardization
    print("[3/6] Fitting StandardScaler...")
    scaler = StandardScaler()
    X_train_scaled = scaler.fit_transform(X_train)
    X_test_scaled = scaler.transform(X_test)

    # 4. Train Baseline (Logistic Regression)
    print("[4/6] Training Baseline Model: Logistic Regression...")
    baseline = LogisticRegression(max_iter=1000, random_state=42, class_weight='balanced')
    baseline.fit(X_train_scaled, y_train)

    y_pred_base = baseline.predict(X_test_scaled)
    y_prob_base = baseline.predict_proba(X_test_scaled)[:, 1]

    metrics_base = {
        'model_name': 'Logistic Regression (Baseline)',
        'accuracy': float(accuracy_score(y_test, y_pred_base)),
        'precision': float(precision_score(y_test, y_pred_base, zero_division=0)),
        'recall': float(recall_score(y_test, y_pred_base)),
        'f1_score': float(f1_score(y_test, y_pred_base)),
        'roc_auc': float(roc_auc_score(y_test, y_prob_base)),
        'confusion_matrix': confusion_matrix(y_test, y_pred_base).tolist(),
    }

    # 5. Train Candidate Model (Random Forest)
    print("[5/6] Training Candidate Model: Random Forest Classifier...")
    rf_model = RandomForestClassifier(
        n_estimators=120,
        max_depth=6,
        min_samples_split=5,
        random_state=42,
        class_weight='balanced'
    )
    rf_model.fit(X_train, y_train)  # Tree-based models work directly on raw features

    y_pred_rf = rf_model.predict(X_test)
    y_prob_rf = rf_model.predict_proba(X_test)[:, 1]

    metrics_rf = {
        'model_name': 'Random Forest Classifier (Candidate)',
        'accuracy': float(accuracy_score(y_test, y_pred_rf)),
        'precision': float(precision_score(y_test, y_pred_rf, zero_division=0)),
        'recall': float(recall_score(y_test, y_pred_rf)),
        'f1_score': float(f1_score(y_test, y_pred_rf)),
        'roc_auc': float(roc_auc_score(y_test, y_prob_rf)),
        'confusion_matrix': confusion_matrix(y_test, y_pred_rf).tolist(),
        'feature_importances': dict(zip(FEATURE_COLUMNS, [float(x) for x in rf_model.feature_importances_])),
    }

    print("\n" + "-" * 50)
    print(" MODEL EVALUATION COMPARISON:")
    print("-" * 50)
    print(f"{'Metric':<20} | {'Baseline (LogReg)':<18} | {'Candidate (RandomForest)':<22}")
    print("-" * 50)
    for m in ['accuracy', 'precision', 'recall', 'f1_score', 'roc_auc']:
        print(f"{m:<20} | {metrics_base[m]:<18.4f} | {metrics_rf[m]:<22.4f}")
    print("-" * 50)

    # Select best model based on F1-score (educational early intervention prioritizes recall & F1)
    if metrics_rf['f1_score'] >= metrics_base['f1_score']:
        selected_model = rf_model
        selected_name = 'RandomForestClassifier'
        selected_metrics = metrics_rf
        requires_scaler = False
        print("=> Selected Model: Random Forest Classifier (Superior F1 & Non-linear capture)")
    else:
        selected_model = baseline
        selected_name = 'LogisticRegression'
        selected_metrics = metrics_base
        requires_scaler = True
        print("=> Selected Model: Logistic Regression")

    # 6. Save Artifacts
    print(f"\n[6/6] Saving artifacts to {artifact_dir}...")
    os.makedirs(artifact_dir, exist_ok=True)

    model_path = os.path.join(artifact_dir, 'dropout_model.joblib')
    scaler_path = os.path.join(artifact_dir, 'scaler.joblib')
    meta_path = os.path.join(artifact_dir, 'model_meta.json')

    joblib.dump(selected_model, model_path)
    joblib.dump(scaler, scaler_path)

    metadata = {
        'version': '1.0.0-BestCoder2026',
        'trained_at': datetime.utcnow().isoformat() + 'Z',
        'selected_model': selected_name,
        'requires_scaler': requires_scaler,
        'feature_columns': FEATURE_COLUMNS,
        'metrics_baseline': metrics_base,
        'metrics_selected': selected_metrics,
        'dataset_info': {
            'total_samples': len(df),
            'positive_dropout_rate': float(df['dropout'].mean()),
            'data_type': 'Synthetic Benchmark Educational Dataset (Section 10.3)',
        }
    }

    with open(meta_path, 'w', encoding='utf-8') as f:
        json.dump(metadata, f, indent=2, ensure_ascii=False)

    print(f" Artifacts saved successfully:")
    print(f"   - Model: {model_path}")
    print(f"   - Scaler: {scaler_path}")
    print(f"   - Meta: {meta_path}")
    print("=" * 60)
    return metadata


if __name__ == '__main__':
    train_models()

