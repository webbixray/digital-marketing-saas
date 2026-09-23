<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Image Generation - Media</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; color: #1e293b; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .header { margin-bottom: 2rem; }
        .header h1 { font-size: 1.75rem; font-weight: 700; }
        .header p { color: #64748b; margin-top: 0.25rem; }

        .form-card { background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.875rem; }
        .form-group textarea { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.875rem; resize: vertical; min-height: 100px; }
        .form-group select { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.875rem; background: #fff; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

        .btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; font-size: 0.875rem; border: none; cursor: pointer; transition: all 0.2s; }
        .btn-primary { background: #4f46e5; color: #fff; }
        .btn-primary:hover { background: #4338ca; }
        .btn-primary:disabled { background: #94a3b8; cursor: not-allowed; }
        .btn-secondary { background: #e2e8f0; color: #475569; }
        .btn-secondary:hover { background: #cbd5e1; }

        .results-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem; }
        .result-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: transform 0.2s; }
        .result-card:hover { transform: translateY(-2px); }
        .result-card img { width: 100%; aspect-ratio: 1; object-fit: cover; background: #f1f5f9; }
        .result-card .actions { padding: 0.75rem; display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .result-card .actions .btn { padding: 0.5rem 0.75rem; font-size: 0.75rem; flex: 1; justify-content: center; }

        .loading { display: none; text-align: center; padding: 3rem; }
        .loading.active { display: block; }
        .spinner { width: 40px; height: 40px; border: 4px solid #e2e8f0; border-top-color: #4f46e5; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .alert { padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.875rem; }
        .alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }

        .empty-state { text-align: center; padding: 3rem; color: #64748b; }
        .empty-state svg { width: 64px; height: 64px; margin-bottom: 1rem; opacity: 0.4; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>AI Image Generation</h1>
            <p>Generate stunning images using artificial intelligence</p>
        </div>

        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="form-card">
            <div class="form-group">
                <label for="prompt">Describe the image you want to generate</label>
                <textarea id="prompt" name="prompt" placeholder="A futuristic city skyline at sunset with flying cars, cyberpunk style..." required></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="style">Style</label>
                    <select id="style" name="style">
                        @foreach($styles as $style)
                            <option value="{{ $style['key'] }}">{{ $style['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="size">Size</label>
                    <select id="size" name="size">
                        @foreach($sizes as $size)
                            <option value="{{ $size['key'] }}">{{ $size['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <button type="button" class="btn btn-primary" id="generateBtn" onclick="generateImage()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 3v18M3 12h18"/>
                </svg>
                Generate Image
            </button>
        </div>

        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>Generating your image... This may take a moment.</p>
        </div>

        <div id="results">
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <path d="M21 15l-5-5L5 21"/>
                </svg>
                <p>Your generated images will appear here</p>
            </div>
        </div>

        <div class="results-grid" id="resultsGrid" style="display:none;"></div>
    </div>

    <script>
        const resultsGrid = document.getElementById('resultsGrid');
        const resultsPlaceholder = document.getElementById('results');
        const loading = document.getElementById('loading');
        const generateBtn = document.getElementById('generateBtn');

        function generateImage() {
            const prompt = document.getElementById('prompt').value.trim();
            const style = document.getElementById('style').value;
            const size = document.getElementById('size').value;

            if (!prompt || prompt.length < 5) {
                alert('Please enter a description of at least 5 characters.');
                return;
            }

            generateBtn.disabled = true;
            loading.classList.add('active');
            resultsPlaceholder.style.display = 'none';

            fetch('{{ route("media.ai.generate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ prompt, style, size }),
            })
            .then(response => response.json())
            .then(data => {
                loading.classList.remove('active');
                generateBtn.disabled = false;

                if (data.success && data.image_url) {
                    addResultToGrid(data);
                } else {
                    alert(data.error || 'Failed to generate image. Please try again.');
                    resultsPlaceholder.style.display = 'block';
                }
            })
            .catch(error => {
                loading.classList.remove('active');
                generateBtn.disabled = false;
                resultsPlaceholder.style.display = 'block';
                alert('An error occurred. Please try again.');
                console.error(error);
            });
        }

        function addResultToGrid(data) {
            resultsGrid.style.display = 'grid';

            const card = document.createElement('div');
            card.className = 'result-card';
            card.innerHTML = `
                <img src="${data.image_url}" alt="${data.prompt}" loading="lazy">
                <div class="actions">
                    <button class="btn btn-primary" onclick="useInPost('${data.image_url}')">Use in Post</button>
                    <button class="btn btn-secondary" onclick="downloadImage('${data.image_url}')">Download</button>
                </div>
            `;
            resultsGrid.insertBefore(card, resultsGrid.firstChild);
        }

        function useInPost(imageUrl) {
            // Store in session for use in post editor
            sessionStorage.setItem('selectedAiImage', imageUrl);
            window.location.href = '{{ route("posts.create") }}?ai_image=' + encodeURIComponent(imageUrl);
        }

        function downloadImage(url) {
            const a = document.createElement('a');
            a.href = url;
            a.download = 'ai-generated-image.jpg';
            a.target = '_blank';
            a.click();
        }

        // Allow Ctrl+Enter to generate
        document.getElementById('prompt').addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'Enter') generateImage();
        });
    </script>
</body>
</html>
