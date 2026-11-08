# Smart LMS - Machine Learning Service (Dropout Risk Prediction)

Dịch vụ Trí tuệ nhân tạo dự đoán nguy cơ bỏ học (Student Dropout Prediction Service) thuộc nền tảng **Smart LMS** (Dự án BestCoder 2026 - BTEC FPT).

## 1. Cấu trúc thư mục

```
ml_service/
├── dataset.py            # Sinh tập dữ liệu giáo dục thực nghiệm (1,500 mẫu)
├── train.py              # Pipeline huấn luyện: Baseline (Logistic Regression) vs Candidate (Random Forest)
├── main.py               # FastAPI server cung cấp RESTful API /health, /predict, /explain
├── requirements.txt      # Thư viện phụ thuộc
└── README.md             # Hướng dẫn chi tiết
```

## 2. Cài đặt môi trường

Yêu cầu: Python 3.10 trở lên.

```bash
# Di chuyển vào thư mục ml_service
cd ml_service

# Tạo môi trường ảo Python
python -m venv venv

# Kích hoạt môi trường ảo
# Trên Windows:
venv\Scripts\activate
# Trên Linux/macOS:
source venv/bin/activate

# Cài đặt các thư viện phụ thuộc
pip install -r requirements.txt
```

## 3. Huấn luyện mô hình

Chạy pipeline huấn luyện để sinh dữ liệu, huấn luyện baseline & candidate, đánh giá độ chính xác (Precision, Recall, F1, ROC-AUC) và xuất artifacts:

```bash
python train.py
```

Kết quả sinh ra các artifacts:
- `dropout_model.joblib`: File nhị phân mô hình tốt nhất (Random Forest)
- `scaler.joblib`: StandardScaler chuẩn hóa dữ liệu
- `model_meta.json`: Metadata về siêu tham số và chỉ số đánh giá

## 4. Khởi chạy FastAPI Server

```bash
uvicorn main:app --reload --host 127.0.0.1 --port 8000
```

- Swagger UI tương tác trực tiếp: `http://127.0.0.1:8000/docs`
- Health check: `http://127.0.0.1:8000/health`

## 5. Các Endpoint chính

### `POST /predict`
Dự đoán nguy cơ bỏ học từ vector đặc trưng học tập:

**Request Body:**
```json
{
  "student_id": "BH02199",
  "gpa": 6.5,
  "passed_subjects": 4,
  "failed_subjects": 1,
  "retake_count": 0,
  "attendance_fail_count": 0,
  "max_absence_rate": 18.5,
  "overall_absence_rate": 12.0,
  "grade_trend": -0.5
}
```

**Response Body:**
```json
{
  "student_id": "BH02199",
  "analysis_date": "2026-11-04 15:40:00",
  "analysis_method": "MACHINE_LEARNING",
  "risk_level": "MEDIUM",
  "risk_score": 38.5,
  "probability": 0.385,
  "data_quality_status": "COMPLETE",
  "model_version": "1.0.0-BestCoder2026",
  "contributing_factors": [...],
  "explanation": "...",
  "recommendations": [...]
}
```

### `POST /explain`
Cung cấp giải thích chi tiết đóng góp của từng nhân tố và mức độ quan trọng toàn cục (Global Feature Importance).

