<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Marketing SaaS API Documentation</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.11.8/swagger-ui.css" />
    <style>
        html { box-sizing: border-box; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin: 0; background: #fafafa; }
        .topbar { background-color: #1a1a2e; }
        .topbar .wrapper { padding: 10px 20px; }
        .topbar .wrapper a { color: #fff; text-decoration: none; }
        .scheme-container { background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.1); margin: 0 0 20px; padding: 15px 20px; }
        .servers-title, .servers > label { color: #3b4151; }
        .swagger-ui .btn.authorize { background-color: #4caf50; border-color: #4caf50; color: #fff; }
        .swagger-ui .btn.authorize svg { fill: #fff; }
        .info .title { color: #1a1a2e; }
        .wrapper { max-width: 1460px; margin: 0 auto; padding: 0 20px; }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="wrapper" style="display: flex; justify-content: space-between; align-items: center;">
            <a href="/"><strong style="color: #fff;">Digital Marketing SaaS</strong></a>
            <div>
                <a href="{{ url('/api/v1/docs/openapi.json') }}" style="margin-right: 20px;">OpenAPI Spec</a>
                <a href="{{ url('/api/v1/docs/postman') }}">Postman Collection</a>
            </div>
        </div>
    </div>
    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5.11.8/swagger-ui-bundle.js" crossorigin></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.11.8/swagger-ui-standalone-preset.js" crossorigin></script>
    <script nonce="{{ $cspNonce ?? '' }}">
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "{{ url('/api/v1/docs/openapi.json') }}",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                supportedSubmitMethods: ['get', 'post', 'put', 'delete', 'patch'],
                requestInterceptor: function(request) {
                    request.headers['X-Requested-With'] = 'XMLHttpRequest';
                    return request;
                },
                responseInterceptor: function(response) {
                    return response;
                },
                onComplete: function() {
                    console.log('Swagger UI loaded successfully');
                }
            });

            // Add authorization input for Bearer token
            const authWrapper = document.querySelector('.auth-wrapper');
            if (authWrapper) {
                const authBtn = authWrapper.querySelector('.authorize');
                if (authBtn) {
                    authBtn.addEventListener('click', function() {
                        const tokenInput = document.querySelector('input[aria-label="auth-bearer-value"]');
                        if (tokenInput) {
                            tokenInput.setAttribute('placeholder', 'Enter your API token');
                        }
                    });
                }
            }

            window.ui = ui;
        };
    </script>
</body>
</html>
