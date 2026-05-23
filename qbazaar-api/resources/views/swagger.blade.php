<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QBazaar API · Swagger</title>
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.18.2/swagger-ui.css">
    <style>
        body { margin: 0; background: #faf6f1; }
        .topbar { display: none; }
        .swagger-ui .info .title { color: #2a2622; }
        .swagger-ui .scheme-container { background: #ffffff; box-shadow: 0 1px 0 #e8e2d8; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5.18.2/swagger-ui-bundle.js" crossorigin></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.18.2/swagger-ui-standalone-preset.js" crossorigin></script>
    <script>
        window.addEventListener('load', () => {
            window.ui = SwaggerUIBundle({
                url: '{{ route('api.v1.openapi') }}',
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset,
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl,
                ],
                layout: 'BaseLayout',
                tryItOutEnabled: true,
                persistAuthorization: true,
                defaultModelsExpandDepth: 1,
                defaultModelExpandDepth: 2,
                docExpansion: 'list',
            });
        });
    </script>
</body>
</html>
