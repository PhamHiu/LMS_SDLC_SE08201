"""
Smart LMS - Student Dropout Dataset Generator
Generates realistic synthetic academic data for machine learning model development
and software testing when historical ground-truth data is not yet available.
Complies with BestCoder 2026 Master Prompt Section X (10.2 & 10.3).
"""

import numpy as np
import pandas as pd


FEATURE_COLUMNS = [
    'gpa',
    'passed_subjects',
    'failed_subjects',
    'retake_count',
    'attendance_fail_count',
    'max_absence_rate',
    'overall_absence_rate',
    'grade_trend',
]


def generate_synthetic_dataset(num_samples: int = 1200, random_seed: int = 42) -> pd.DataFrame:
    """
    Generates a realistic educational dataset simulating student academic journeys.
    Dropout status (target = 1) is influenced by:
    - High absence rates (> 20% in subjects)
    - Low GPA (< 5.0)
    - Multiple failed subjects and retakes
    - Negative grade trajectory
    """
    np.random.seed(random_seed)

    # 1. GPA: normal distribution centered at 6.8, clipped between 1.0 and 10.0
    gpa = np.clip(np.random.normal(loc=6.8, scale=1.7, size=num_samples), 1.0, 10.0)
    gpa = np.round(gpa, 2)

    # 2. Total subjects per term (usually 4 to 6)
    total_subjects = np.random.randint(4, 7, size=num_samples)

    # 3. Failed subjects: inversely correlated with GPA
    fail_prob = np.clip((8.5 - gpa) / 10.0, 0.02, 0.85)
    failed_subjects = np.random.binomial(n=total_subjects, p=fail_prob)
    passed_subjects = total_subjects - failed_subjects

    # 4. Retake count: depends on failed subjects
    retake_count = np.array([
        np.random.randint(0, min(f + 1, 4)) if f > 0 else 0
        for f in failed_subjects
    ])

    # 5. Absence rates
    # Overall absence rate: beta distribution skewed low, with a subset of high-absence students
    is_truant = np.random.rand(num_samples) < 0.18
    regular_absence = np.random.beta(a=1.5, b=8.0, size=num_samples) * 30.0  # 0% - 30%
    truant_absence = np.random.beta(a=4.0, b=4.0, size=num_samples) * 50.0 + 15.0  # 15% - 65%
    overall_absence_rate = np.where(is_truant, truant_absence, regular_absence)
    overall_absence_rate = np.clip(np.round(overall_absence_rate, 1), 0.0, 80.0)

    # Max absence rate across single subjects (always >= overall_absence_rate)
    single_subj_spike = np.random.exponential(scale=6.0, size=num_samples)
    max_absence_rate = np.clip(np.round(overall_absence_rate + single_subj_spike, 1), 0.0, 100.0)

    # Attendance fail count (subjects with absence > 20.0%)
    attendance_fail_count = np.zeros(num_samples, dtype=int)
    for i in range(num_samples):
        if max_absence_rate[i] > 20.0:
            # At least 1 subject failed attendance, possibly more if overall is high
            attendance_fail_count[i] = min(
                int(1 + (overall_absence_rate[i] // 18)),
                total_subjects[i]
            )

    # 6. Grade trend: change from previous semester (-3.0 to +3.0)
    grade_trend = np.clip(np.random.normal(loc=0.05, scale=0.8, size=num_samples), -3.0, 3.0)
    grade_trend = np.round(grade_trend, 2)

    # 7. Ground Truth: Dropout probability calculation (Logistic Log-odds formulation)
    # Log-odds formula combining key domain risk factors:
    logits = (
        -3.2                                   # Intercept (baseline retention ~85%)
        - 0.55 * (gpa - 5.0)                   # Low GPA drastically increases risk
        + 0.85 * failed_subjects               # Each failed subject increases risk
        + 0.50 * retake_count                  # Retake debt
        + 0.95 * attendance_fail_count         # Attendance failure is high predictor
        + 0.04 * (overall_absence_rate - 15.0) # Overall absenteeism
        - 0.60 * grade_trend                   # Improving grades buffer risk
    )

    dropout_prob = 1.0 / (1.0 + np.exp(-logits))
    # Binary target from probability
    dropout = (np.random.rand(num_samples) < dropout_prob).astype(int)

    df = pd.DataFrame({
        'student_id': [f'STU{1000 + i}' for i in range(num_samples)],
        'gpa': gpa,
        'passed_subjects': passed_subjects,
        'failed_subjects': failed_subjects,
        'retake_count': retake_count,
        'attendance_fail_count': attendance_fail_count,
        'max_absence_rate': max_absence_rate,
        'overall_absence_rate': overall_absence_rate,
        'grade_trend': grade_trend,
        'dropout': dropout,
    })

    return df


if __name__ == '__main__':
    df = generate_synthetic_dataset(1500)
    print(f"Generated {len(df)} samples.")
    print(f"Dropout rate: {df['dropout'].mean() * 100:.2f}%")
    print(df.describe())
    df.to_csv('synthetic_student_dropout_data.csv', index=False)
    print("Saved to synthetic_student_dropout_data.csv")

