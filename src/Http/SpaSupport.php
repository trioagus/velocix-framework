<?php

namespace Velocix\Http;

trait SpaSupport
{
    /**
     * Check if request is from SPA router
     */
    protected function isSpaRequest()
    {
        return isset($_SERVER['HTTP_X_VELOCIX_SPA']) && 
               $_SERVER['HTTP_X_VELOCIX_SPA'] === 'true';
    }

    /**
     * Render view with SPA support
     * - Full page for normal requests (SSR)
     * - Partial HTML for SPA requests
     */
    protected function spaView($view, $data = [])
    {
        if ($this->isSpaRequest()) {
            // Return ONLY the content, not full layout
            return $this->renderPartial($view, $data);
        }

        // Return full page with layout (SSR)
        return view($view, $data);
    }

    /**
     * Render partial content without layout
     */
    protected function renderPartial($view, $data = [])
    {
        // Render FULL HTML first (semua @extends dan @section sudah di-compile)
        $fullHtml = view($view, $data)->render();
        
        // Method 1: Coba dengan DOMDocument
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(mb_convert_encoding($fullHtml, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//*[@data-velocix-content]');
        
        if ($nodes->length > 0) {
            $contentNode = $nodes->item(0);
            $innerHTML = '';
            
            foreach ($contentNode->childNodes as $child) {
                $innerHTML .= $dom->saveHTML($child);
            }
            
            if (trim($innerHTML)) {
                header('Content-Type: application/json');
                echo json_encode([
                    'html' => $innerHTML,
                    'title' => $data['title'] ?? null
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
        }
        
        // Method 2: Fallback dengan regex yang lebih baik
        if (preg_match('/<[^>]*data-velocix-content[^>]*>(.*?)<\/(?:main|div)>/is', $fullHtml, $matches)) {
            header('Content-Type: application/json');
            echo json_encode([
                'html' => trim($matches[1]),
                'title' => $data['title'] ?? null
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        
        // Method 3: Last resort - return everything inside <main>
        if (preg_match('/<main[^>]*>(.*?)<\/main>/is', $fullHtml, $matches)) {
            header('Content-Type: application/json');
            echo json_encode([
                'html' => trim($matches[1]),
                'title' => $data['title'] ?? null
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        
        // Debug fallback
        error_log("SpaSupport: Failed to extract content, returning full HTML");
        header('Content-Type: application/json');
        echo json_encode([
            'html' => $fullHtml,
            'title' => $data['title'] ?? null,
            'debug' => 'extraction_failed'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}