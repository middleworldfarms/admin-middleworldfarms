<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Test Customer Action Endpoints</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h1><i class="fas fa-test-tube"></i> Test Customer Action Endpoints</h1>
                    </div>
                    <div class="card-body">
                        <p>Testing the new dedicated customer action endpoints with Emma Garner (User ID: 22)</p>
                        
                        <div class="mb-3">
                            <label for="userId" class="form-label">Customer User ID:</label>
                            <input type="number" class="form-control" id="userId" value="22">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <button type="button" 
                                        class="btn btn-info w-100 mb-2 action-button" 
                                        data-action-type="profile">
                                    <i class="fas fa-user"></i> Test Profile
                                </button>
                            </div>
                            <div class="col-md-4">
                                <button type="button" 
                                        class="btn btn-warning w-100 mb-2 action-button" 
                                        data-action-type="subscriptions">
                                    <i class="fas fa-sync"></i> Test Subscriptions
                                </button>
                            </div>
                            <div class="col-md-4">
                                <button type="button" 
                                        class="btn btn-success w-100 mb-2 action-button" 
                                        data-action-type="orders">
                                    <i class="fas fa-shopping-cart"></i> Test Orders
                                </button>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3" id="results" style="display: none;"></div>
                        
                        <hr>
                        <a href="/admin/deliveries" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Deliveries
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle action buttons
        document.querySelectorAll('.action-button').forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                
                const actionType = this.dataset.actionType;
                const userId = document.getElementById('userId').value;
                const originalText = this.innerHTML;
                const resultsDiv = document.getElementById('results');
                
                if (!userId) {
                    resultsDiv.innerHTML = '❌ Please enter a valid User ID';
                    resultsDiv.style.display = 'block';
                    resultsDiv.className = 'alert alert-danger mt-3';
                    return;
                }
                
                // Show loading state
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
                this.disabled = true;
                
                // Map action types to API endpoints
                const endpoints = {
                    'profile': '/admin/customer/profile',
                    'subscriptions': '/admin/customer/subscriptions',
                    'orders': '/admin/customer/orders'
                };
                
                const endpoint = endpoints[actionType];
                
                // Call the dedicated API endpoint
                fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: parseInt(userId)
                    })
                })
                .then(response => response.json())
                .then(data => {
                    resultsDiv.style.display = 'block';
                    
                    if (data.success) {
                        resultsDiv.className = 'alert alert-success mt-3';
                        resultsDiv.innerHTML = `
                            ✅ <strong>${actionType.charAt(0).toUpperCase() + actionType.slice(1)} endpoint successful!</strong><br>
                            🔗 Preview URL: <a href="${data.preview_url}" target="_blank">${data.preview_url}</a><br>
                            👤 User: ${data.user.display_name} (${data.user.email})
                        `;
                        
                        // Auto-open the preview URL
                        window.open(data.preview_url, '_blank');
                    } else {
                        resultsDiv.className = 'alert alert-danger mt-3';
                        resultsDiv.innerHTML = '❌ Error: ' + (data.message || 'Failed to access customer ' + actionType);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultsDiv.style.display = 'block';
                    resultsDiv.className = 'alert alert-danger mt-3';
                    resultsDiv.innerHTML = '❌ Network error: Unable to access customer ' + actionType;
                })
                .finally(() => {
                    // Restore button state
                    this.innerHTML = originalText;
                    this.disabled = false;
                });
            });
        });
    });
    </script>
</body>
</html>
