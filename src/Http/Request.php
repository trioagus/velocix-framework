<?php

namespace Velocix\Http;

class Request
{
    protected $query;
    protected $request;
    protected $server;
    protected $files;
    protected $cookies;
    protected $headers;

    public static function capture()
    {
        $request = new static();
        $request->query = $_GET;
        $request->request = $_POST;
        $request->server = $_SERVER;
        $request->files = $_FILES;
        $request->cookies = $_COOKIE;
        $request->headers = getallheaders() ?: [];
        
        return $request;
    }

    /**
     * Get the HTTP method with support for method spoofing
     * FIXED: Tambah support untuk _method field
     * 
     * @return string
     */
    public function method()
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
        
        // Support method spoofing untuk POST requests
        if ($method === 'POST') {
            // Check _method di POST data
            if (isset($this->request['_method'])) {
                $spoofedMethod = strtoupper($this->request['_method']);
                
                // Hanya allow PUT, PATCH, DELETE
                if (in_array($spoofedMethod, ['PUT', 'PATCH', 'DELETE'])) {
                    return $spoofedMethod;
                }
            }
            
            // Check _method di JSON body
            $json = $this->json();
            if (isset($json['_method'])) {
                $spoofedMethod = strtoupper($json['_method']);
                
                if (in_array($spoofedMethod, ['PUT', 'PATCH', 'DELETE'])) {
                    return $spoofedMethod;
                }
            }
        }
        
        return $method;
    }
    
    /**
     * Get the original HTTP method (without spoofing)
     * 
     * @return string
     */
    public function getRealMethod()
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }
    
    /**
     * Check if method is spoofed
     * 
     * @return bool
     */
    public function isMethodSpoofed()
    {
        return $this->method() !== $this->getRealMethod();
    }

    public function path()
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        return parse_url($uri, PHP_URL_PATH);
    }

    public function url()
    {
        $protocol = (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') 
            ? 'https' : 'http';
        $host = $this->server['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host . $this->path();
    }

    public function input($key = null, $default = null)
    {
        $input = array_merge($this->query, $this->request, $this->json());
        
        if (is_null($key)) {
            return $input;
        }
        
        return $input[$key] ?? $default;
    }

    public function all()
    {
        return $this->input();
    }

    public function only($keys)
    {
        $results = [];
        $input = $this->all();
        
        foreach ((array) $keys as $key) {
            if (isset($input[$key])) {
                $results[$key] = $input[$key];
            }
        }
        
        return $results;
    }

    public function except($keys)
    {
        $input = $this->all();
        
        foreach ((array) $keys as $key) {
            unset($input[$key]);
        }
        
        return $input;
    }

    public function json()
    {
        $content = file_get_contents('php://input');
        return json_decode($content, true) ?? [];
    }

    public function isJson()
    {
        return strpos($this->header('Content-Type', ''), 'application/json') !== false;
    }

    public function expectsJson()
    {
        return $this->isJson() || $this->wantsJson();
    }

    public function wantsJson()
    {
        $acceptable = $this->header('Accept', '');
        return strpos($acceptable, 'application/json') !== false;
    }

    public function ajax()
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Check if request is from Velocix SPA
     * 
     * @return bool
     */
    public function isSpaRequest()
    {
        return $this->header('X-Velocix-Spa') === 'true';
    }

    public function header($key, $default = null)
    {
        $key = str_replace('_', '-', strtolower($key));
        
        foreach ($this->headers as $header => $value) {
            if (strtolower($header) === $key) {
                return $value;
            }
        }
        
        return $default;
    }

    public function bearerToken()
    {
        $header = $this->header('Authorization', '');
        
        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Check if the request has a file
     * 
     * @param string $key
     * @return bool
     */
    public function hasFile($key)
    {
        if (!isset($this->files[$key])) {
            return false;
        }
        
        $file = $this->files[$key];
        
        // Check if file was uploaded
        if (is_array($file)) {
            // Handle multiple files or single file
            if (isset($file['error'])) {
                return $file['error'] !== UPLOAD_ERR_NO_FILE;
            }
            
            // Array of files
            return !empty($file);
        }
        
        return false;
    }

    /**
     * Get uploaded file
     * 
     * @param string $key
     * @return UploadedFile|null
     */
    public function file($key)
    {
        if (!$this->hasFile($key)) {
            return null;
        }
        
        $file = $this->files[$key];
        
        return new UploadedFile(
            $file['tmp_name'],
            $file['name'],
            $file['type'],
            $file['size'],
            $file['error']
        );
    }

    /**
     * Get all uploaded files
     * 
     * @return array
     */
    public function allFiles()
    {
        $files = [];
        
        foreach ($this->files as $key => $file) {
            if ($this->hasFile($key)) {
                $files[$key] = $this->file($key);
            }
        }
        
        return $files;
    }
}

/**
 * UploadedFile class to handle file uploads
 */
class UploadedFile
{
    protected $path;
    protected $originalName;
    protected $mimeType;
    protected $size;
    protected $error;

    public function __construct($path, $originalName, $mimeType, $size, $error)
    {
        $this->path = $path;
        $this->originalName = $originalName;
        $this->mimeType = $mimeType;
        $this->size = $size;
        $this->error = $error;
    }

    /**
     * Get the original filename
     * 
     * @return string
     */
    public function getClientOriginalName()
    {
        return $this->originalName;
    }

    /**
     * Get the file extension
     * 
     * @return string
     */
    public function getClientOriginalExtension()
    {
        return pathinfo($this->originalName, PATHINFO_EXTENSION);
    }

    /**
     * Get the mime type
     * 
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * Get the file size
     * 
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Get the upload error code
     * 
     * @return int
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Check if upload was successful
     * 
     * @return bool
     */
    public function isValid()
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    /**
     * Get the temporary file path
     * 
     * @return string
     */
    public function getPathname()
    {
        return $this->path;
    }

    /**
     * Move the uploaded file to a new location
     * 
     * @param string $directory
     * @param string|null $name
     * @return string|false The final file path or false on failure
     */
    public function move($directory, $name = null)
    {
        if (!$this->isValid()) {
            return false;
        }

        // Create directory if it doesn't exist
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Generate filename if not provided
        if (is_null($name)) {
            $name = $this->hashName();
        }

        $target = rtrim($directory, '/') . '/' . $name;

        if (move_uploaded_file($this->path, $target)) {
            return $target;
        }

        return false;
    }

    /**
     * Store the file in a directory with a hashed name
     * 
     * @param string $directory
     * @return string|false
     */
    public function store($directory)
    {
        return $this->move($directory, $this->hashName());
    }

    /**
     * Generate a unique filename
     * 
     * @return string
     */
    protected function hashName()
    {
        $hash = bin2hex(random_bytes(16));
        $extension = $this->getClientOriginalExtension();
        
        return $hash . ($extension ? '.' . $extension : '');
    }

    /**
     * Get file contents
     * 
     * @return string|false
     */
    public function get()
    {
        return file_get_contents($this->path);
    }
}