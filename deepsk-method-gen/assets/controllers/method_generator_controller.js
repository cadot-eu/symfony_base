< !DOCTYPE html >
    <html lang="en">
        <head>
            <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>deepseek AI Method Generator</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
                        <script src="https://cdn.jsdelivr.net/npm/@hotwired/stimulus/dist/stimulus.umd.js"></script>
                        <script src="app.js" defer></script>
                    </head>
                    <body>
                        <div class="container mt-5">
                            <h1>deepseek AI Method Generator</h1>
                            <form data-controller="method-generator" data-action="submit->method-generator#generateMethod">
                                <div class="mb-3">
                                    <label for="file" class="form-label">Select File</label>
                                    <select id="file" class="form-select" name="file" required>
                                        <!-- Options will be populated dynamically -->
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="methodName" class="form-label">Method Name</label>
                                    <input type="text" id="methodName" class="form-control" name="methodName" required>
                                </div>
                                <div class="mb-3">
                                    <label for="parameters" class="form-label">Parameters (name:type, comma separated)</label>
                                    <input type="text" id="parameters" class="form-control" name="parameters" required>
                                </div>
                                <div class="mb-3">
                                    <label for="returnType" class="form-label">Return Type</label>
                                    <input type="text" id="returnType" class="form-control" name="returnType" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Generate Method</button>
                            </form>

                            <div class="mt-4">
                                <h2>Log</h2>
                                <pre id="log" class="border p-3" style="height: 200px; overflow-y: auto;"></pre>
                            </div>
                        </div>
                    </body>
                </html>