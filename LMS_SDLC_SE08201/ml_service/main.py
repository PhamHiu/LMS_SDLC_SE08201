"""
Smart LMS - Student Dropout Prediction FastAPI Service
Provides real-time ML inference and model explainability for the Smart LMS platform.
Complies with BestCoder 2026 Master Prompt Section X and XI.
"""

import os       # Thư viện hệ thống: quản lý đường dẫn file model (.joblib, .json) và đọc biến môi trường (VI_EMBED_MODEL)
import json     # Đọc/ghi siêu dữ liệu cấu hình mô hình (model_meta.json)
import joblib   # Thư viện nạp mô hình Machine Learning đã huấn luyện (dropout_model.joblib, scaler.joblib)
import numpy as np   # Thư viện toán học ma trận/vector: chuyển đổi mảng đặc trưng và xử lý mảng SHAP values
import pandas as pd  # Thư viện cấu trúc bảng dữ liệu phục vụ tiền xử lý đặc trưng

try:
    import shap  # Thư viện SHAP (Lundberg & Lee, 2017): tính giá trị Shapley giải thích đóng góp từng đặc trưng (Explainable AI)
    _SHAP_AVAILABLE = True
except Exception:  # pragma: no cover - môi trường chưa cài shap vẫn chạy được (bỏ qua giải thích SHAP)
    shap = None
    _SHAP_AVAILABLE = False
from datetime import datetime                       # Ghi nhận mốc thời gian thực hiện phân tích dự đoán
from typing import List, Optional, Dict, Any        # Type Hinting chuẩn Python cho kiểu dữ liệu tĩnh rõ ràng
from pydantic import BaseModel, Field               # Thư viện Pydantic: xác thực dữ liệu (Validation) request/response tự động
from fastapi import FastAPI, HTTPException          # Thư viện FastAPI: khởi tạo REST API endpoints và bắt lỗi HTTP
from fastapi.middleware.cors import CORSMiddleware  # Middleware CORS: cho phép Laravel backend gọi chéo cổng sang FastAPI

# Initialize FastAPI App
app = FastAPI(
    title="Smart LMS - AI Dropout Risk Prediction API",
    description="Machine Learning service predicting academic dropout risk and providing explainability factors.",
    version="1.0.0-BestCoder2026"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Paths to artifacts
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
MODEL_PATH = os.path.join(BASE_DIR, "dropout_model.joblib")
SCALER_PATH = os.path.join(BASE_DIR, "scaler.joblib")
META_PATH = os.path.join(BASE_DIR, "model_meta.json")

# In-memory model cache
loaded_model = None
loaded_scaler = None
model_metadata = {}
shap_explainer = None  # SHAP TreeExplainer dựng từ model đã nạp (dùng cho mô hình cây như Random Forest)

FEATURE_COLUMNS = [
    'gpa',
    'passed_subjects',
    'failed_subjects',
    'retake_count',
    'attendance_fail_count',
    'max_absence_rate',
    'overall_absence_rate',
    'grade_trend',
    'overdue_assignments',
]

# Nhãn tiếng Việt cho từng đặc trưng (phục vụ hiển thị giải thích SHAP)
FEATURE_LABELS = {
    'gpa': 'Điểm trung bình (GPA)',
    'passed_subjects': 'Số môn đạt',
    'failed_subjects': 'Số môn nợ',
    'retake_count': 'Số lần học lại',
    'attendance_fail_count': 'Số môn trượt vắng >20%',
    'max_absence_rate': 'Tỷ lệ vắng cao nhất',
    'overall_absence_rate': 'Tỷ lệ vắng trung bình',
    'grade_trend': 'Xu hướng điểm',
    'overdue_assignments': 'Số bài quá hạn',
}


def load_artifacts():
    global loaded_model, loaded_scaler, model_metadata, shap_explainer
    if os.path.exists(META_PATH):
        try:
            with open(META_PATH, "r", encoding="utf-8") as f:
                model_metadata = json.load(f)
        except Exception as e:
            print(f"Error loading meta: {e}")

    if os.path.exists(MODEL_PATH):
        try:
            loaded_model = joblib.load(MODEL_PATH)
            print("Successfully loaded dropout_model.joblib")
        except Exception as e:
            print(f"Error loading model: {e}")

    if os.path.exists(SCALER_PATH):
        try:
            loaded_scaler = joblib.load(SCALER_PATH)
            print("Successfully loaded scaler.joblib")
        except Exception as e:
            print(f"Error loading scaler: {e}")

    # Dựng SHAP explainer từ model cây (Random Forest) để giải thích từng dự đoán.
    shap_explainer = None
    if _SHAP_AVAILABLE and loaded_model is not None and not model_metadata.get("requires_scaler"):
        try:
            shap_explainer = shap.TreeExplainer(loaded_model)
            print("Successfully initialized SHAP TreeExplainer")
        except Exception as e:
            print(f"SHAP explainer init failed (bỏ qua giải thích SHAP): {e}")
            shap_explainer = None


@app.on_event("startup")
def startup_event():
    load_artifacts()


# --- Pydantic Request & Response Schemas ---
class StudentFeatures(BaseModel):
    student_id: Optional[str] = Field(default="STU_DEMO", description="Mã sinh viên")
    gpa: float = Field(default=7.0, ge=0.0, le=10.0, description="Điểm trung bình tích lũy")
    passed_subjects: int = Field(default=4, ge=0, description="Số môn đạt")
    failed_subjects: int = Field(default=0, ge=0, description="Số môn trượt điểm")
    retake_count: int = Field(default=0, ge=0, description="Số môn học lại trong quá khứ")
    attendance_fail_count: int = Field(default=0, ge=0, description="Số môn trượt vì vắng > 20%")
    max_absence_rate: float = Field(default=0.0, ge=0.0, le=100.0, description="Tỷ lệ vắng cao nhất của 1 môn (%)")
    overall_absence_rate: float = Field(default=0.0, ge=0.0, le=100.0, description="Tỷ lệ vắng trung bình tất cả các môn (%)")
    grade_trend: float = Field(default=0.0, ge=-10.0, le=10.0, description="Xu hướng điểm so với kỳ trước")
    overdue_assignments: int = Field(default=0, ge=0, description="Số bài tập nộp trễ hoặc chưa nộp sau hạn")


class ContributingFactor(BaseModel):
    factor_type: str
    title: str
    description: str
    severity: str
    impact_weight: float


class ShapContribution(BaseModel):
    feature: str
    label: str
    value: float
    shap_value: float
    direction: str  # "INCREASE" (tăng nguy cơ) hoặc "DECREASE" (giảm nguy cơ)


class PredictionResponse(BaseModel):
    student_id: str
    analysis_date: str
    analysis_method: str
    risk_level: str
    risk_score: float
    probability: float
    data_quality_status: str
    model_version: str
    contributing_factors: List[ContributingFactor]
    shap_explanation: List[ShapContribution]
    explanation: str
    recommendations: List[str]


# --- Helper Methods ---
def classify_risk_level(prob: float, features: StudentFeatures) -> str:
    """
    Combines ML probability with critical academic rules:
    Rule 7.1: Truancy in attendance or high failures guarantees high risk.
    """
    if features.attendance_fail_count >= 2 or features.failed_subjects >= 3 or prob >= 0.75:
        return "VERY_HIGH"
    elif features.attendance_fail_count >= 1 or features.failed_subjects >= 1 or prob >= 0.50:
        return "HIGH"
    elif features.max_absence_rate >= 15.0 or prob >= 0.25:
        return "MEDIUM"
    else:
        return "SAFE"


def generate_factors(features: StudentFeatures, prob: float) -> List[ContributingFactor]:
    factors = []

    # Attendance check (>20% rule)
    if features.attendance_fail_count > 0:
        factors.append(ContributingFactor(
            factor_type="ATTENDANCE",
            title=f"Đã trượt {features.attendance_fail_count} môn do vắng vượt 20%",
            description=f"Tỷ lệ vắng cao nhất đạt {features.max_absence_rate}%, vi phạm quy chế đào tạo.",
            severity="CRITICAL",
            impact_weight=0.90
        ))
    elif features.max_absence_rate >= 15.0:
        factors.append(ContributingFactor(
            factor_type="ATTENDANCE",
            title=f"Cảnh báo vắng học ({features.max_absence_rate}%)",
            description="Tỷ lệ vắng đang tiến sát ngưỡng cấm thi 20%. Cần đi học đầy đủ các buổi còn lại.",
            severity="WARNING",
            impact_weight=0.55
        ))

    # Academic Grade check
    if features.failed_subjects > 0:
        factors.append(ContributingFactor(
            factor_type="ACADEMIC",
            title=f"Nợ {features.failed_subjects} môn học (chưa đạt điểm qua môn)",
            description=f"Điểm trung bình hiện tại: {features.gpa}/10.",
            severity="HIGH" if features.failed_subjects >= 2 else "WARNING",
            impact_weight=0.80
        ))
    elif features.gpa < 5.0:
        factors.append(ContributingFactor(
            factor_type="ACADEMIC",
            title="Điểm trung bình dưới mức trung bình (< 5.0)",
            description=f"GPA tích lũy hiện tại là {features.gpa}/10.",
            severity="WARNING",
            impact_weight=0.50
        ))

    # Retake history
    if features.retake_count > 0:
        factors.append(ContributingFactor(
            factor_type="RETROSPECTIVE",
            title=f"Lịch sử từng học lại {features.retake_count} lần",
            description="Có nguy cơ tích tụ nợ tín chỉ và kéo dài thời gian tốt nghiệp.",
            severity="INFO",
            impact_weight=0.40
        ))

    # Overdue assignments (bài tập quá hạn / chưa nộp)
    if features.overdue_assignments >= 3:
        factors.append(ContributingFactor(
            factor_type="ENGAGEMENT",
            title=f"Tồn đọng {features.overdue_assignments} bài tập quá hạn",
            description="Số bài nộp trễ/chưa nộp cao cho thấy sự sa sút mức độ tham gia học tập.",
            severity="HIGH",
            impact_weight=0.65
        ))
    elif features.overdue_assignments >= 1:
        factors.append(ContributingFactor(
            factor_type="ENGAGEMENT",
            title=f"Có {features.overdue_assignments} bài tập quá hạn",
            description="Cần hoàn thành các bài tập còn thiếu để không tích tụ nợ bài.",
            severity="WARNING",
            impact_weight=0.45
        ))

    # Grade trajectory
    if features.grade_trend < -1.0:
        factors.append(ContributingFactor(
            factor_type="TREND",
            title="Xu hướng học tập sụt giảm nghiêm trọng",
            description=f"Điểm trung bình sụt giảm {abs(features.grade_trend):.1f} điểm so với kỳ trước.",
            severity="WARNING",
            impact_weight=0.60
        ))
    elif features.grade_trend > 0.5:
        factors.append(ContributingFactor(
            factor_type="POSITIVE",
            title="Xu hướng học tập cải thiện tích cực",
            description=f"Điểm trung bình tăng {features.grade_trend:.1f} điểm so với kỳ trước.",
            severity="INFO",
            impact_weight=-0.30
        ))

    if not factors:
        factors.append(ContributingFactor(
            factor_type="ATTENDANCE",
            title="Chuyên cần và học tập xuất sắc",
            description="Không có môn nào trượt, tỷ lệ chuyên cần đảm bảo trên 85%.",
            severity="INFO",
            impact_weight=0.0
        ))

    return factors


def generate_recommendations(risk_level: str, features: StudentFeatures) -> List[str]:
    recs = []
    if risk_level == "VERY_HIGH":
        recs.append("Cố vấn học tập và giáo viên chủ nhiệm cần gặp trực tiếp sinh viên ngay trong tuần.")
        recs.append("Lập kế hoạch học lại và bảo lưu tín chỉ hợp lý để giảm tải áp lực.")
        recs.append("Yêu cầu sinh viên tham gia ôn tập phụ đạo với AI Tutor hàng ngày.")
    elif risk_level == "HIGH":
        recs.append("Giáo viên bộ môn nhắc nhở học sinh duy trì đi học 100% các buổi còn lại.")
        recs.append("Sử dụng AI Tutor để ôn tập các chủ đề bị hổng kiến thức trước kỳ thi.")
    elif risk_level == "MEDIUM":
        recs.append("Cảnh giác với số buổi vắng, không được nghỉ thêm bất kỳ buổi nào.")
        recs.append("Tăng cường hoàn thành các bài tập và quiz trắc nghiệm trên hệ thống LMS.")
    else:
        recs.append("Duy trì phong độ học tập và chuyên cần hiện tại.")
        recs.append("Khuyến khích nghiên cứu sâu thêm các môn chuyên ngành qua AI Learning Tutor.")
    return recs


def compute_shap_contributions(raw_vector: np.ndarray) -> List[ShapContribution]:
    """
    Tính đóng góp SHAP (Lundberg & Lee, 2017) cho MỘT dự đoán cụ thể.
    Trả về danh sách từng đặc trưng kèm giá trị SHAP (dương = đẩy nguy cơ tăng,
    âm = kéo nguy cơ giảm), sắp xếp theo mức ảnh hưởng tuyệt đối giảm dần.
    """
    if shap_explainer is None:
        return []

    try:
        shap_output = shap_explainer.shap_values(raw_vector)

        # Chuẩn hóa đầu ra SHAP về vector đóng góp cho lớp "nguy cơ" (class = 1)
        if isinstance(shap_output, list):
            # Phiên bản cũ: list [class_0, class_1]
            contrib = np.asarray(shap_output[1])[0]
        else:
            arr = np.asarray(shap_output)
            if arr.ndim == 3:
                # (n_samples, n_features, n_classes) -> lấy mẫu 0, lớp 1
                contrib = arr[0, :, 1]
            else:
                contrib = arr[0]

        contrib = np.asarray(contrib, dtype=float).ravel()
        values = raw_vector[0]

        contributions = []
        for i, feat in enumerate(FEATURE_COLUMNS):
            if i >= len(contrib):
                break
            sv = float(contrib[i])
            contributions.append(ShapContribution(
                feature=feat,
                label=FEATURE_LABELS.get(feat, feat),
                value=round(float(values[i]), 3),
                shap_value=round(sv, 4),
                direction="INCREASE" if sv >= 0 else "DECREASE",
            ))

        contributions.sort(key=lambda c: abs(c.shap_value), reverse=True)
        return contributions
    except Exception as e:
        print(f"SHAP computation failed: {e}")
        return []


# --- API Routes ---
@app.get("/health")
def health_check():
    return {
        "status": "healthy",
        "service": "Smart LMS ML Dropout Prediction Service",
        "model_loaded": loaded_model is not None,
        "shap_available": _SHAP_AVAILABLE,
        "shap_explainer_ready": shap_explainer is not None,
        "model_metadata": model_metadata,
        "timestamp": datetime.utcnow().isoformat() + "Z"
    }


@app.post("/predict", response_model=PredictionResponse)
def predict_dropout(features: StudentFeatures):
    # Form input feature vector
    raw_vector = np.array([[
        features.gpa,
        features.passed_subjects,
        features.failed_subjects,
        features.retake_count,
        features.attendance_fail_count,
        features.max_absence_rate,
        features.overall_absence_rate,
        features.grade_trend,
        features.overdue_assignments
    ]])

    # Calculate model probability if model is loaded, otherwise use statistical baseline
    if loaded_model is not None:
        try:
            if model_metadata.get("requires_scaler") and loaded_scaler is not None:
                scaled_vector = loaded_scaler.transform(raw_vector)
                prob = float(loaded_model.predict_proba(scaled_vector)[0][1])
            else:
                prob = float(loaded_model.predict_proba(raw_vector)[0][1])
            method = "MACHINE_LEARNING"
            version = model_metadata.get("version", "1.0.0-BestCoder2026")
        except Exception as e:
            # Fallback to analytical calculation
            prob = min(max((features.attendance_fail_count * 0.35 + features.failed_subjects * 0.25 + (10 - features.gpa) * 0.05 + features.overdue_assignments * 0.03), 0.05), 0.95)
            method = "ANALYTICAL_BASELINE"
            version = "1.0.0-Fallback"
    else:
        # Heuristic baseline matching formula
        prob = min(max((features.attendance_fail_count * 0.35 + features.failed_subjects * 0.25 + (10 - features.gpa) * 0.05 + features.overdue_assignments * 0.03), 0.05), 0.95)
        method = "ANALYTICAL_BASELINE"
        version = "1.0.0-Pretrained"

    risk_level = classify_risk_level(prob, features)
    risk_score = round(prob * 100.0, 1)
    factors = generate_factors(features, prob)
    recommendations = generate_recommendations(risk_level, features)

    # Giải thích minh bạch từng dự đoán bằng SHAP (chỉ có khi dùng model cây đã nạp)
    shap_explanation = compute_shap_contributions(raw_vector) if method == "MACHINE_LEARNING" else []

    explanation = (
        f"Mô hình dự đoán xác suất nguy cơ bỏ học là {prob * 100:.1f}%. "
        f"Xếp loại nguy cơ: {risk_level}. "
        f"Các nhân tố chính: GPA={features.gpa}, Số môn nợ={features.failed_subjects}, "
        f"Số môn trượt vắng >20%={features.attendance_fail_count}, Vắng cao nhất={features.max_absence_rate}%, "
        f"Bài quá hạn={features.overdue_assignments}."
    )

    if shap_explanation:
        top = shap_explanation[0]
        huong = "làm tăng" if top.direction == "INCREASE" else "làm giảm"
        explanation += f" Theo SHAP, yếu tố ảnh hưởng mạnh nhất là '{top.label}' ({huong} nguy cơ)."

    return PredictionResponse(
        student_id=features.student_id or "STU_UNKNOWN",
        analysis_date=datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
        analysis_method=method,
        risk_level=risk_level,
        risk_score=risk_score,
        probability=round(prob, 4),
        data_quality_status="COMPLETE",
        model_version=version,
        contributing_factors=factors,
        shap_explanation=shap_explanation,
        explanation=explanation,
        recommendations=recommendations
    )


@app.post("/explain")
def explain_factors(features: StudentFeatures):
    pred = predict_dropout(features)
    importances = model_metadata.get("metrics_selected", {}).get("feature_importances", {})
    return {
        "student_id": features.student_id,
        "risk_level": pred.risk_level,
        "probability": pred.probability,
        "contributing_factors": pred.contributing_factors,
        "shap_explanation": pred.shap_explanation,
        "global_feature_importance": importances,
        "explanation": pred.explanation
    }


# =========================================================================
# PHẦN 2 TRONG PIPELINE RAG: ĐỘNG CƠ NHÚNG VECTOR TIẾNG VIỆT (EMBEDDING ENGINE)
# - Model: AITeamVN/Vietnamese_Embedding_v2 (HuggingFace - fine-tuned trên BGE-M3 tiếng Việt).
# - Vai trò: Chuyển một đoạn văn bản tiếng Việt (câu hỏi sinh viên hoặc chunk quy chế/học liệu)
#   thành vector số thực 1024 chiều đã chuẩn hóa độ dài L2 (normalize_embeddings=True).
# - Được gọi bởi PHP Laravel (GeminiEmbeddingService::embedViaLocalMl) ở Tầng 1 của Waterfall.
# =========================================================================
EMBED_MODEL_ID = os.environ.get("VI_EMBED_MODEL", "AITeamVN/Vietnamese_Embedding_v2")
_embedder = None


class EmbedRequest(BaseModel):
    text: str = Field(..., min_length=1, max_length=16000)


def get_embedder():
    """
    Lazy-loading: Chỉ khởi tạo và nạp trọng số model vào RAM khi có request /embed đầu tiên,
    giúp FastAPI khởi động tức thì mà không làm nghẽn API dự đoán bỏ học (/predict).
    """
    global _embedder
    if _embedder is None:
        from sentence_transformers import SentenceTransformer
        _embedder = SentenceTransformer(EMBED_MODEL_ID)
    return _embedder


@app.post("/embed")
def embed_text(req: EmbedRequest):
    """
    API nhận văn bản tiếng Việt và trả về mảng vector float 1024 chiều phục vụ Hybrid RAG.
    """
    try:
        model = get_embedder()
        vec = model.encode(req.text, normalize_embeddings=True).tolist()
        return {
            "model": EMBED_MODEL_ID,
            "dimension": len(vec),
            "embedding": vec,
        }
    except Exception as e:
        raise HTTPException(status_code=503, detail=f"Vietnamese_Embedding_v2 unavailable: {e}")


# =========================================================================
# TÓM TẮT TÀI LIỆU TIẾNG VIỆT (VietAI/vit5-base-vietnews-summarization)
# & NÉN NGỮ CẢNH HỘI THOẠI (Microsoft LLMLingua-2)
# =========================================================================
VIT5_MODEL_ID = os.environ.get("VIT5_SUMMARIZE_MODEL", "VietAI/vit5-base-vietnews-summarization")
_vit5_tokenizer = None
_vit5_model = None
_llmlingua_compressor = None


class SummarizeRequest(BaseModel):
    text: Optional[str] = Field(default=None, max_length=60000)
    file_path: Optional[str] = Field(default=None, max_length=1000)
    max_length: int = Field(default=256, ge=64, le=512)


class CompressContextRequest(BaseModel):
    context: str = Field(..., min_length=1, max_length=60000)
    rate: float = Field(default=0.5, ge=0.2, le=0.9)


def extract_text_from_file(file_path: str) -> str:
    """Bóc tách văn bản từ file PDF (PyMuPDF), DOCX (python-docx), PPTX (python-pptx) hoặc TXT."""
    if not file_path or not os.path.exists(file_path):
        return ""
    ext = os.path.splitext(file_path)[1].lower()
    if ext == ".pdf":
        import fitz  # PyMuPDF
        doc = fitz.open(file_path)
        return "\n".join(page.get_text("text") for page in doc).strip()
    if ext == ".docx":
        import docx  # python-docx
        d = docx.Document(file_path)
        return "\n".join(p.text for p in d.paragraphs if p.text.strip()).strip()
    if ext == ".pptx":
        from pptx import Presentation  # python-pptx
        prs = Presentation(file_path)
        texts = []
        for slide in prs.slides:
            for shape in slide.shapes:
                if hasattr(shape, "text") and shape.text.strip():
                    texts.append(shape.text.strip())
        return "\n".join(texts).strip()
    with open(file_path, "r", encoding="utf-8", errors="ignore") as f:
        return f.read().strip()


def get_vit5():
    """Lazy-loading mô hình VietAI/vit5-base-vietnews-summarization."""
    global _vit5_tokenizer, _vit5_model
    if _vit5_tokenizer is None or _vit5_model is None:
        from transformers import AutoTokenizer, AutoModelForSeq2SeqLM
        _vit5_tokenizer = AutoTokenizer.from_pretrained(VIT5_MODEL_ID)
        _vit5_model = AutoModelForSeq2SeqLM.from_pretrained(VIT5_MODEL_ID)
    return _vit5_tokenizer, _vit5_model


def get_llmlingua():
    """Lazy-loading Microsoft LLMLingua-2 (xlm-roberta đa ngôn ngữ hỗ trợ tiếng Việt)."""
    global _llmlingua_compressor
    if _llmlingua_compressor is None:
        from llmlingua import PromptCompressor
        _llmlingua_compressor = PromptCompressor(
            model_name="microsoft/llmlingua-2-xlm-roberta-large-meetingbank",
            use_llmlingua2=True,
            device_map="cpu",
        )
    return _llmlingua_compressor


@app.post("/summarize")
def summarize_document(req: SummarizeRequest):
    """
    Tóm tắt tài liệu học tiếng Việt bằng VietAI/vit5-base-vietnews-summarization.
    Hỗ trợ nhận trực tiếp text hoặc đường dẫn file PDF/DOCX/PPTX/TXT.
    """
    raw_text = (req.text or "").strip()
    if req.file_path:
        extracted = extract_text_from_file(req.file_path)
        if extracted:
            raw_text = f"{raw_text}\n{extracted}".strip() if raw_text else extracted

    if not raw_text:
        raise HTTPException(status_code=400, detail="Không có nội dung văn bản để tóm tắt.")

    try:
        tokenizer, model = get_vit5()
        # Cắt đoạn 2500 ký tự đầu để tránh vượt ngưỡng 1024 token của ViT5
        input_text = "vietnews: " + raw_text[:2500] + " </s>"
        encoding = tokenizer(input_text, return_tensors="pt", max_length=1024, truncation=True)
        outputs = model.generate(
            input_ids=encoding["input_ids"],
            attention_mask=encoding["attention_mask"],
            max_length=req.max_length,
            min_length=48,
            num_beams=4,
            early_stopping=True,
            no_repeat_ngram_size=3,
        )
        summary = tokenizer.decode(outputs[0], skip_special_tokens=True, clean_up_tokenization_spaces=True)
        return {
            "model": VIT5_MODEL_ID,
            "summary": summary,
            "extracted_chars": len(raw_text),
        }
    except Exception as e:
        raise HTTPException(status_code=503, detail=f"ViT5 summarization unavailable: {e}")


@app.post("/compress-context")
def compress_context(req: CompressContextRequest):
    """
    Nén ngữ cảnh hội thoại dài bằng Microsoft LLMLingua-2 (tự fallback sang ViT5 nếu chưa cài llmlingua).
    """
    try:
        compressor = get_llmlingua()
        result = compressor.compress_prompt(req.context, rate=req.rate, force_tokens=["\n", ".", ":"])
        return {
            "method": "LLMLINGUA_2",
            "compressed_context": result.get("compressed_prompt", req.context),
            "origin_tokens": result.get("origin_tokens", 0),
            "compressed_tokens": result.get("compressed_tokens", 0),
        }
    except Exception:
        # Fallback sang ViT5 nếu chưa cài llmlingua
        try:
            res = summarize_document(SummarizeRequest(text=req.context, max_length=180))
            return {
                "method": "VIT5_SUMMARY",
                "compressed_context": res["summary"],
            }
        except Exception as e:
            raise HTTPException(status_code=503, detail=f"Context compression unavailable: {e}")



