{{-- Global Mermaid.js + Highlight.js (VS Code Dark+ theme) Runtime cho câu trả lời AI --}}
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/vs2015.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.min.js"></script>

<style>
    .vscode-code-block {
        background-color: #1e1e1e;
        border: 1px solid #333333;
        border-radius: 8px;
        overflow: hidden;
        margin: 12px 0;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.14);
    }
    .vscode-code-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background-color: #252526;
        border-bottom: 1px solid #333333;
        padding: 6px 12px;
        font-family: 'Be Vietnam Pro', system-ui, sans-serif;
        font-size: 11px;
        color: #cccccc;
    }
    .vscode-code-lang {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-family: 'Cascadia Code', 'JetBrains Mono', Consolas, monospace;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        color: #9cdcfe;
        letter-spacing: 0.04em;
    }
    .vscode-copy-btn {
        background: #333333;
        color: #e5e7eb;
        border: 1px solid #454545;
        border-radius: 4px;
        padding: 2px 8px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.15s ease;
    }
    .vscode-copy-btn:hover {
        background: #3e3e42;
        color: #ffffff;
    }
    .vscode-code-block pre {
        margin: 0 !important;
        padding: 12px 14px !important;
        background-color: #1e1e1e !important;
        overflow-x: auto;
        border: none !important;
        border-radius: 0 !important;
    }
    .vscode-code-block pre code,
    .vscode-code-block pre code.hljs {
        font-family: 'Cascadia Code', 'JetBrains Mono', Consolas, 'Courier New', monospace !important;
        font-size: 13px !important;
        line-height: 1.65 !important;
        background: transparent !important;
        color: #d4d4d4;
        padding: 0 !important;
        tab-size: 4;
    }
    /* Chuẩn màu VS Code Dark+ */
    .vscode-code-block .hljs-keyword,
    .vscode-code-block .hljs-selector-tag,
    .vscode-code-block .hljs-literal { color: #569cd6 !important; font-weight: 500; }
    .vscode-code-block .hljs-built_in,
    .vscode-code-block .hljs-title.function_,
    .vscode-code-block .hljs-function .hljs-title { color: #dcdcaa !important; }
    .vscode-code-block .hljs-string,
    .vscode-code-block .hljs-regexp { color: #ce9178 !important; }
    .vscode-code-block .hljs-number { color: #b5cea8 !important; }
    .vscode-code-block .hljs-comment,
    .vscode-code-block .hljs-quote { color: #6a9955 !important; font-style: italic; }
    .vscode-code-block .hljs-variable,
    .vscode-code-block .hljs-params,
    .vscode-code-block .hljs-attr { color: #9cdcfe !important; }
    .vscode-code-block .hljs-title.class_,
    .vscode-code-block .hljs-type { color: #4ec9b0 !important; }
    .vscode-code-block .hljs-meta { color: #c586c0 !important; }
    .vscode-inline-code {
        font-family: 'Cascadia Code', 'JetBrains Mono', Consolas, 'Courier New', monospace;
        font-size: 0.85em;
        background-color: #1e1e1e;
        color: #9cdcfe;
        padding: 1px 6px;
        border-radius: 4px;
        border: 1px solid #333333;
    }
    /* Mermaid Diagram — hiện đại, không cắt chữ tiếng Việt */
    .ai-mermaid-canvas {
        background-color: #fafcff;
        background-image: radial-gradient(#dbe4f0 1px, transparent 1px);
        background-size: 16px 16px;
        overflow-x: auto;
        overflow-y: hidden;
    }
    .ai-mermaid-canvas svg {
        height: auto;
        overflow: visible !important;
        display: block;
    }
    .ai-mermaid-canvas svg foreignObject {
        overflow: visible !important;
    }
    .ai-mermaid-canvas svg .nodeLabel,
    .ai-mermaid-canvas svg .label,
    .ai-mermaid-canvas svg .edgeLabel {
        font-family: 'Be Vietnam Pro', 'Segoe UI', system-ui, sans-serif !important;
        font-size: 13.5px !important;
        font-weight: 600 !important;
        line-height: 1.4 !important;
        white-space: nowrap;
    }
    .ai-mermaid-canvas svg .node rect,
    .ai-mermaid-canvas svg .node polygon,
    .ai-mermaid-canvas svg .node circle {
        rx: 10px;
        ry: 10px;
        stroke-width: 1.8px !important;
        filter: drop-shadow(0 3px 8px rgba(30, 79, 143, 0.10));
    }
    .ai-mermaid-canvas svg .node:first-of-type rect {
        fill: #1e4f8f !important;
        stroke: #163c6d !important;
    }
    .ai-mermaid-canvas svg .node:first-of-type .nodeLabel,
    .ai-mermaid-canvas svg .node:first-of-type .nodeLabel p,
    .ai-mermaid-canvas svg .node:first-of-type span,
    .ai-mermaid-canvas svg .node:first-of-type text {
        color: #ffffff !important;
        fill: #ffffff !important;
        font-weight: 700 !important;
    }
    .ai-mermaid-canvas svg .edgePath path.path {
        stroke: #64748b !important;
        stroke-width: 1.8px !important;
    }
    .ai-mermaid-canvas svg marker path {
        fill: #64748b !important;
        stroke: #64748b !important;
    }
</style>

{{-- Lightbox Modal phóng to sơ đồ UML --}}
<div id="aiMermaidZoomModal" class="fixed inset-0 z-[99999] hidden items-center justify-center bg-slate-900/80 p-4" onclick="if(event.target === this) window.closeMermaidZoom()">
    <div class="relative flex max-h-[92vh] w-full max-w-5xl flex-col rounded-lg bg-white shadow-xl border border-slate-200 overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3">
            <span id="aiMermaidZoomTitle" class="text-sm font-bold text-slate-800">Sơ đồ minh họa (UML / Mermaid)</span>
            <div class="flex items-center gap-2">
                <button type="button" onclick="window.downloadZoomedMermaidSvg()" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">
                    Tải SVG
                </button>
                <button type="button" onclick="window.closeMermaidZoom()" class="rounded-md bg-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-300 transition">
                    Đóng ✕
                </button>
            </div>
        </div>
        <div id="aiMermaidZoomBody" class="flex-1 overflow-auto p-6 flex items-center justify-center bg-white min-h-[320px]"></div>
    </div>
</div>

<script>
(function() {
    if (window.__aiMermaidInit) return;
    window.__aiMermaidInit = true;

    let mermaidReady = false;
    let renderCounter = 0;

    function initMermaid() {
        if (mermaidReady || typeof window.mermaid === 'undefined') return;
        window.mermaid.initialize({
            startOnLoad: false,
            theme: 'base',
            securityLevel: 'loose',
            fontFamily: 'Be Vietnam Pro, Segoe UI, system-ui, sans-serif',
            themeVariables: {
                primaryColor: '#eff6ff',
                primaryTextColor: '#0f172a',
                primaryBorderColor: '#3b82f6',
                secondaryColor: '#f0fdf4',
                secondaryTextColor: '#14532d',
                secondaryBorderColor: '#22c55e',
                tertiaryColor: '#fff7ed',
                tertiaryTextColor: '#7c2d12',
                tertiaryBorderColor: '#f97316',
                lineColor: '#64748b',
                textColor: '#0f172a',
                mainBkg: '#eff6ff',
                nodeBorder: '#3b82f6',
                clusterBkg: '#f8fafc',
                clusterBorder: '#cbd5e1',
                edgeLabelBackground: '#ffffff',
                fontSize: '14px'
            },
            flowchart: {
                htmlLabels: true,
                curve: 'basis',
                useMaxWidth: false,
                padding: 18,
                nodeSpacing: 36,
                rankSpacing: 48
            },
            sequence: { useMaxWidth: true, actorMargin: 45 },
            class: { useMaxWidth: true },
            er: { useMaxWidth: true }
        });
        mermaidReady = true;
    }

    function detectDiagramLabel(code) {
        const first = (code || '').trim().split(/\r?\n/)[0].toLowerCase();
        if (first.startsWith('sequencediagram')) return 'UML Sequence Diagram (Biểu đồ tuần tự)';
        if (first.startsWith('classdiagram')) return 'UML Class Diagram (Biểu đồ lớp)';
        if (first.startsWith('erdiagram')) return 'ER Diagram (Sơ đồ thực thể liên kết)';
        if (first.startsWith('statediagram')) return 'UML State Diagram (Biểu đồ trạng thái)';
        if (first.startsWith('flowchart') || first.startsWith('graph')) return 'Flowchart (Lưu đồ quy trình)';
        return 'Sơ đồ minh họa (UML / Mermaid)';
    }

    function sanitizeMermaidSource(raw) {
        let code = (raw || '').trim();
        // Gỡ fence thừa nếu LLM lồng trùng
        code = code.replace(/^```mermaid\s*/i, '').replace(/```$/i, '').trim();
        // Loại bỏ thẻ HTML đơn giản gây lỗi parse
        code = code.replace(/<br\s*\/?>/gi, ' - ').replace(/<\/?(b|strong|i|em|u)>/gi, '');
        return code;
    }

    window.buildMermaidCardHtml = function(rawCode) {
        const div = document.createElement('div');
        div.textContent = rawCode || '';
        const escaped = div.innerHTML;
        const label = detectDiagramLabel(rawCode);

        return `
            <div class="ai-mermaid-container my-3 rounded-lg border border-slate-200 bg-white shadow-xs overflow-hidden" data-rendered="false">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 bg-slate-50 px-3 py-2 text-[11px]">
                    <span class="ai-mermaid-label font-bold text-[#1e4f8f]">${label}</span>
                    <div class="flex items-center gap-1.5">
                        <button type="button" onclick="window.zoomMermaidCard(this)" class="rounded border border-slate-300 bg-white px-2 py-0.5 font-semibold text-slate-700 hover:bg-slate-100 transition">Phóng to</button>
                        <button type="button" onclick="window.downloadMermaidCardSvg(this)" class="rounded border border-slate-300 bg-white px-2 py-0.5 font-semibold text-slate-700 hover:bg-slate-100 transition">Tải SVG</button>
                        <button type="button" onclick="window.toggleMermaidSource(this)" class="rounded border border-slate-300 bg-white px-2 py-0.5 font-semibold text-slate-600 hover:bg-slate-100 transition">Mã nguồn</button>
                    </div>
                </div>
                <div class="ai-mermaid-canvas p-3 overflow-x-auto flex justify-center bg-white min-h-[72px]">
                    <span class="text-xs text-slate-400 py-4">Đang vẽ sơ đồ...</span>
                </div>
                <pre class="ai-mermaid-src hidden border-t border-slate-200 bg-slate-900 text-slate-100 p-3 text-[11px] overflow-x-auto m-0 rounded-none">${escaped}</pre>
            </div>
        `;
    };

    window.buildVsCodeBlockHtml = function(lang, rawCode) {
        const div = document.createElement('div');
        div.textContent = rawCode || '';
        const escaped = div.innerHTML;
        const cleanLang = (lang || 'code').trim().toLowerCase().replace(/[^a-z0-9_+#-]/g, '') || 'code';
        const displayLang = cleanLang === 'code' ? 'TEXT' : cleanLang.toUpperCase();

        return `
            <div class="vscode-code-block" data-highlighted="false">
                <div class="vscode-code-header">
                    <span class="vscode-code-lang">&lt;/&gt; ${displayLang}</span>
                    <button type="button" class="vscode-copy-btn" onclick="window.copyVsCodeBlock(this)">Sao chép</button>
                </div>
                <pre><code class="language-${cleanLang}">${escaped}</code></pre>
            </div>
        `;
    };

    window.highlightVsCodeBlocks = function(rootEl) {
        if (typeof window.hljs === 'undefined') return;
        const scope = rootEl || document;
        const blocks = scope.querySelectorAll('.vscode-code-block[data-highlighted="false"] code');
        blocks.forEach(function(codeEl) {
            try {
                window.hljs.highlightElement(codeEl);
            } catch (e) {}
            const wrapper = codeEl.closest('.vscode-code-block');
            if (wrapper) wrapper.setAttribute('data-highlighted', 'true');
        });
    };

    window.copyVsCodeBlock = function(btn) {
        const box = btn.closest('.vscode-code-block');
        const codeEl = box ? box.querySelector('pre code') : null;
        if (!codeEl) return;
        const text = codeEl.textContent || '';
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                const old = btn.textContent;
                btn.textContent = 'Đã chép ✓';
                setTimeout(function() { btn.textContent = old; }, 1600);
            });
        }
    };

    window.renderMermaidDiagrams = async function(rootEl) {
        window.highlightVsCodeBlocks(rootEl);
        initMermaid();
        if (!mermaidReady) return;

        const scope = rootEl || document;
        const containers = scope.querySelectorAll('.ai-mermaid-container[data-rendered="false"]');
        for (const box of containers) {
            box.setAttribute('data-rendered', 'rendering');
            const srcEl = box.querySelector('.ai-mermaid-src');
            const canvasEl = box.querySelector('.ai-mermaid-canvas');
            const labelEl = box.querySelector('.ai-mermaid-label');
            if (!srcEl || !canvasEl) continue;

            const code = sanitizeMermaidSource(srcEl.textContent);
            if (!code) {
                box.setAttribute('data-rendered', 'error');
                continue;
            }
            if (labelEl) labelEl.textContent = detectDiagramLabel(code);

            try {
                renderCounter += 1;
                const id = 'ai_mmd_' + Date.now() + '_' + renderCounter;
                const result = await window.mermaid.render(id, code);
                canvasEl.innerHTML = result.svg;
                const svg = canvasEl.querySelector('svg');
                if (svg) {
                    const vb = svg.viewBox && svg.viewBox.baseVal;
                    const naturalW = (vb && vb.width > 0) ? vb.width : parseFloat(svg.getAttribute('width') || '0');
                    const containerW = canvasEl.clientWidth - 24;
                    if (naturalW > 0 && containerW > 0 && naturalW > containerW) {
                        // Không bóp nhỏ quá 0.85x để chữ luôn to rõ; nếu rộng hơn thì cho cuộn ngang mượt
                        const displayW = Math.max(Math.round(naturalW * 0.88), containerW);
                        svg.style.width = displayW + 'px';
                        svg.style.minWidth = displayW + 'px';
                        svg.style.maxWidth = 'none';
                        canvasEl.style.justifyContent = 'flex-start';
                    } else {
                        svg.style.maxWidth = '100%';
                        canvasEl.style.justifyContent = 'center';
                    }
                    svg.style.height = 'auto';
                }
                box.setAttribute('data-rendered', 'true');
            } catch (err) {
                // Dọn thẻ lỗi tạm của Mermaid nếu có
                const orphan = document.getElementById('d' + ('ai_mmd_' + Date.now() + '_' + renderCounter));
                if (orphan) orphan.remove();
                canvasEl.innerHTML = '<div class="w-full text-left"><p class="text-[11px] text-amber-700 mb-1.5 font-semibold">Không thể dựng hình tự động, hiển thị mã sơ đồ:</p><pre class="bg-slate-900 text-slate-100 p-3 rounded text-[11px] overflow-x-auto m-0">' + srcEl.innerHTML + '</pre></div>';
                box.setAttribute('data-rendered', 'error');
            }
        }
    };

    window.toggleMermaidSource = function(btn) {
        const box = btn.closest('.ai-mermaid-container');
        const srcEl = box ? box.querySelector('.ai-mermaid-src') : null;
        if (srcEl) srcEl.classList.toggle('hidden');
    };

    window.downloadMermaidCardSvg = function(btn) {
        const box = btn.closest('.ai-mermaid-container');
        const svg = box ? box.querySelector('.ai-mermaid-canvas svg') : null;
        if (!svg) return;
        const blob = new Blob([svg.outerHTML], { type: 'image/svg+xml;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'so-do-uml-' + Date.now() + '.svg';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    };

    window.zoomMermaidCard = function(btn) {
        const box = btn.closest('.ai-mermaid-container');
        const svg = box ? box.querySelector('.ai-mermaid-canvas svg') : null;
        const label = box ? (box.querySelector('.ai-mermaid-label')?.textContent || 'Sơ đồ minh họa') : 'Sơ đồ minh họa';
        if (!svg) return;

        const modal = document.getElementById('aiMermaidZoomModal');
        const body = document.getElementById('aiMermaidZoomBody');
        const title = document.getElementById('aiMermaidZoomTitle');
        if (!modal || !body) return;

        title.textContent = label;
        body.innerHTML = svg.outerHTML;
        const zoomedSvg = body.querySelector('svg');
        if (zoomedSvg) {
            zoomedSvg.style.width = '100%';
            zoomedSvg.style.maxHeight = '76vh';
            zoomedSvg.style.height = 'auto';
        }
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    window.closeMermaidZoom = function() {
        const modal = document.getElementById('aiMermaidZoomModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    };

    window.downloadZoomedMermaidSvg = function() {
        const svg = document.querySelector('#aiMermaidZoomBody svg');
        if (!svg) return;
        const blob = new Blob([svg.outerHTML], { type: 'image/svg+xml;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'so-do-uml-' + Date.now() + '.svg';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    };

    document.addEventListener('DOMContentLoaded', function() {
        window.renderMermaidDiagrams(document);
    });
})();
</script>
