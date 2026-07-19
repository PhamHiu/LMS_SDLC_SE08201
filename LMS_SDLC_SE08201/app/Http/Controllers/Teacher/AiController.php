<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\AI\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

// ( Đăng thêm ) Controller xử lý request sinh câu hỏi bằng AI
class AiController extends Controller
{
    private GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function generate(Request $request)
    {
        $request->validate([
            'topic' => 'required|string|max:255',
            'count' => 'required|integer|min:1|max:50',
            'document' => 'nullable|file|mimes:txt,pdf,docx|max:5120', // 5MB max
        ]);

        $topic = $request->input('topic');
        $count = $request->input('count');
        $documentText = null;
        $inlineData = null;

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $extension = $file->getClientOriginalExtension();
            $path = $file->getRealPath();

            try {
                if ($extension === 'txt') {
                    $documentText = file_get_contents($path);
                } elseif ($extension === 'pdf') {
                    // Send PDF as inlineData directly to Gemini
                    $inlineData = [
                        'mimeType' => 'application/pdf',
                        'data' => base64_encode(file_get_contents($path))
                    ];
                } elseif ($extension === 'docx') {
                    // Extract raw text from docx xml
                    $documentText = $this->extractTextFromDocx($path);
                }
            } catch (\Exception $e) {
                Log::error('AI Document Read Error: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể đọc file tài liệu: ' . $e->getMessage()
                ], 422);
            }
        }

        try {
            $questions = $this->geminiService->generateQuestions($topic, $count, $documentText, $inlineData);
            return response()->json([
                'success' => true,
                'data' => $questions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi kết nối AI: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hàm bóc tách chữ từ file DOCX (ZIP -> word/document.xml)
     */
    private function extractTextFromDocx(string $filePath): string
    {
        $zip = new \ZipArchive();
        $text = '';
        if ($zip->open($filePath) === true) {
            $xmlIndex = $zip->locateName('word/document.xml');
            if ($xmlIndex !== false) {
                $xmlData = $zip->getFromIndex($xmlIndex);
                $xml = new \DOMDocument();
                $xml->loadXML($xmlData, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
                $text = strip_tags($xml->saveXML());
            }
            $zip->close();
        } else {
            throw new \Exception('Không thể giải nén file DOCX');
        }
        return $text;
    }
}
