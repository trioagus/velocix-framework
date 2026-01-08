<?php

namespace Velocix\View;

class Compiler
{
    protected $extensions = [];

    public function compile($content)
    {
        // Compile custom directives first
        foreach ($this->extensions as $extension) {
            $content = $extension($content);
        }

        // Compile @extends and @section BEFORE other directives
        $content = $this->compileLayoutSystem($content);
        
        // Compile control structures
        $content = $this->compileForeach($content);
        $content = $this->compileEndForeach($content);
        
        $content = $this->compileFor($content);
        $content = $this->compileEndFor($content);
        
        $content = $this->compileWhile($content);
        $content = $this->compileEndWhile($content);
        
        $content = $this->compileIf($content);
        $content = $this->compileElseif($content);
        $content = $this->compileElse($content);
        $content = $this->compileEndIf($content);

        // Compile includes
        $content = $this->compileInclude($content);
        
        // Compile echoes (LAST)
        $content = $this->compileRawEchos($content);
        $content = $this->compileEchos($content);
        
        return $content;
    }

    protected function compileLayoutSystem($content)
    {
        // Check if this view extends a layout
        if (preg_match('/@extends\s*\(\s*[\'"](.+?)[\'"]\s*\)/', $content, $extendsMatch)) {
            $layout = $extendsMatch[1];
            
            // Extract all sections
            $sections = [];
            
            // FIRST: Handle inline sections like @section('title', 'value')
            preg_match_all('/@section\s*\(\s*[\'"]([^\'\"]+?)[\'"]\s*,\s*[\'"]([^\'"]*?)[\'"]\s*\)/s', $content, $inlineSections);
            
            foreach ($inlineSections[1] as $index => $sectionName) {
                $sections[$sectionName] = $inlineSections[2][$index];
            }
            
            // Remove inline sections from content
            $content = preg_replace('/@section\s*\(\s*[\'"]([^\'\"]+?)[\'"]\s*,\s*[\'"]([^\'"]*?)[\'"]\s*\)/s', '', $content);
            
            // SECOND: Handle block sections @section('name') ... @endsection
            preg_match_all('/@section\s*\(\s*[\'"]([^\'\"]+?)[\'"]\s*\)/s', $content, $sectionStarts, PREG_OFFSET_CAPTURE);
            
            foreach ($sectionStarts[0] as $index => $startMatch) {
                $sectionName = $sectionStarts[1][$index][0];
                $startPos = $startMatch[1] + strlen($startMatch[0]);
                
                // Find matching @endsection
                $depth = 1;
                $pos = $startPos;
                $endPos = false;
                
                while ($depth > 0 && $pos < strlen($content)) {
                    $nextSection = strpos($content, '@section', $pos);
                    $nextEnd = strpos($content, '@endsection', $pos);
                    
                    if ($nextEnd === false) break;
                    
                    if ($nextSection !== false && $nextSection < $nextEnd) {
                        $depth++;
                        $pos = $nextSection + 8;
                    } else {
                        $depth--;
                        if ($depth === 0) {
                            $endPos = $nextEnd;
                        }
                        $pos = $nextEnd + 11;
                    }
                }
                
                if ($endPos !== false) {
                    $sectionContent = substr($content, $startPos, $endPos - $startPos);
                    $sections[$sectionName] = trim($sectionContent);
                }
            }
            
            // Read layout file
            $layoutPath = str_replace('.', '/', $layout) . '.vlx.php';
            $basePath = app()->basePath();
            $fullLayoutPath = $basePath . '/resources/views/' . $layoutPath;
            
            if (!file_exists($fullLayoutPath)) {
                throw new \Exception("Layout not found: {$fullLayoutPath}");
            }
            
            $layoutContent = file_get_contents($fullLayoutPath);
            
            // Replace @yield with section content
            foreach ($sections as $name => $sectionContent) {
                $pattern = '/@yield\s*\(\s*[\'"]\s*' . preg_quote($name, '/') . '\s*[\'"]\s*(?:,\s*[\'"].*?[\'"])?\s*\)/';
                $layoutContent = preg_replace(
                    $pattern,
                    $sectionContent,
                    $layoutContent
                );
            }
            
            // Replace remaining @yield with empty string or default
            $layoutContent = preg_replace_callback(
                '/@yield\s*\(\s*[\'"](.+?)[\'"]\s*(?:,\s*[\'"](.+?)[\'"])?\s*\)/',
                function($matches) {
                    return $matches[2] ?? '';
                },
                $layoutContent
            );
            
            return $layoutContent;
        }
        
        // No extends, just remove section tags
        $content = preg_replace('/@section\s*\(\s*[\'"](.+?)[\'"]\s*,\s*[\'"]([^\'"]*?)[\'"]\s*\)/s', '', $content);
        $content = preg_replace('/@section\s*\(\s*[\'"](.+?)[\'"]\s*\)/', '', $content);
        $content = preg_replace('/@endsection/', '', $content);
        
        return $content;
    }

    protected function compileInclude($content)
    {
        return preg_replace_callback(
            '/@include\s*\(\s*[\'"](.+?)[\'"]\s*\)/',
            function($matches) {
                $view = $matches[1];
                $viewPath = str_replace('.', '/', $view) . '.vlx.php';
                $basePath = app()->basePath();
                $fullPath = $basePath . '/resources/views/' . $viewPath;
                
                if (file_exists($fullPath)) {
                    return file_get_contents($fullPath);
                }
                
                return "<!-- Include not found: {$view} -->";
            },
            $content
        );
    }

    protected function compileIf($content)
    {
        return preg_replace_callback(
            '/@if\s*\(/',
            function($matches) use (&$content) {
                static $offset = 0;
                
                $pos = strpos($content, $matches[0], $offset);
                if ($pos === false) return $matches[0];
                
                $start = $pos + strlen($matches[0]);
                $depth = 1;
                $i = $start;
                
                while ($depth > 0 && $i < strlen($content)) {
                    if ($content[$i] === '(') {
                        $depth++;
                    } elseif ($content[$i] === ')') {
                        $depth--;
                    }
                    $i++;
                }
                
                if ($depth === 0) {
                    $condition = substr($content, $start, $i - $start - 1);
                    $offset = $i;
                    return '<?php if(' . $condition . '): ?>';
                }
                
                return $matches[0];
            },
            $content
        );
    }

    protected function compileElseif($content)
    {
        return preg_replace_callback(
            '/@elseif\s*\(/',
            function($matches) use (&$content) {
                static $offset = 0;
                
                $pos = strpos($content, $matches[0], $offset);
                if ($pos === false) return $matches[0];
                
                $start = $pos + strlen($matches[0]);
                $depth = 1;
                $i = $start;
                
                while ($depth > 0 && $i < strlen($content)) {
                    if ($content[$i] === '(') {
                        $depth++;
                    } elseif ($content[$i] === ')') {
                        $depth--;
                    }
                    $i++;
                }
                
                if ($depth === 0) {
                    $condition = substr($content, $start, $i - $start - 1);
                    $offset = $i;
                    return '<?php elseif(' . $condition . '): ?>';
                }
                
                return $matches[0];
            },
            $content
        );
    }

    protected function compileElse($content)
    {
        return preg_replace('/@else\b/', '<?php else: ?>', $content);
    }

    protected function compileEndIf($content)
    {
        return preg_replace('/@endif\b/', '<?php endif; ?>', $content);
    }

    protected function compileFor($content)
    {
        return preg_replace('/@for\s*\((.+?)\)/s', '<?php for($1): ?>', $content);
    }

    protected function compileEndFor($content)
    {
        return preg_replace('/@endfor\b/', '<?php endfor; ?>', $content);
    }

    protected function compileForeach($content)
    {
        return preg_replace('/@foreach\s*\((.+?)\)/s', '<?php foreach($1): ?>', $content);
    }

    protected function compileEndForeach($content)
    {
        return preg_replace('/@endforeach\b/', '<?php endforeach; ?>', $content);
    }

    protected function compileWhile($content)
    {
        return preg_replace_callback(
            '/@while\s*\(/',
            function($matches) use (&$content) {
                static $offset = 0;
                
                $pos = strpos($content, $matches[0], $offset);
                if ($pos === false) return $matches[0];
                
                $start = $pos + strlen($matches[0]);
                $depth = 1;
                $i = $start;
                
                while ($depth > 0 && $i < strlen($content)) {
                    if ($content[$i] === '(') {
                        $depth++;
                    } elseif ($content[$i] === ')') {
                        $depth--;
                    }
                    $i++;
                }
                
                if ($depth === 0) {
                    $condition = substr($content, $start, $i - $start - 1);
                    $offset = $i;
                    return '<?php while(' . $condition . '): ?>';
                }
                
                return $matches[0];
            },
            $content
        );
    }

    protected function compileEndWhile($content)
    {
        return preg_replace('/@endwhile\b/', '<?php endwhile; ?>', $content);
    }

    protected function compileEchos($content)
    {
        return preg_replace(
            '/\{\{\s*(.+?)\s*\}\}/s',
            '<?php echo htmlspecialchars((string)($1), ENT_QUOTES, \'UTF-8\'); ?>',
            $content
        );
    }

    protected function compileRawEchos($content)
    {
        return preg_replace(
            '/\{!!\s*(.+?)\s*!!\}/s',
            '<?php echo $1; ?>',
            $content
        );
    }

    public function extend($callback)
    {
        $this->extensions[] = $callback;
    }
}