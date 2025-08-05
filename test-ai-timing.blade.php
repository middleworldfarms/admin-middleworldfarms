<!DOCTYPE html>
<html>
<head>
    <title>AI Crop Timing Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <h1>AI Crop Timing Test</h1>
    <button onclick="testAI()">Test AI Timing for Lettuce</button>
    <div id="result"></div>

    <script>
    async function testAI() {
        try {
            console.log('Testing AI endpoint...');
            
            const response = await fetch('/admin/api/ai/crop-timing', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    crop_type: 'lettuce',
                    season: 'summer',
                    is_direct_sow: false
                })
            });
            
            console.log('Response status:', response.status);
            
            if (response.ok) {
                const result = await response.json();
                console.log('Result:', result);
                document.getElementById('result').innerHTML = '<pre>' + JSON.stringify(result, null, 2) + '</pre>';
            } else {
                const error = await response.text();
                console.error('Error response:', error);
                document.getElementById('result').innerHTML = 'Error: ' + error;
            }
            
        } catch (error) {
            console.error('Fetch error:', error);
            document.getElementById('result').innerHTML = 'Fetch error: ' + error.message;
        }
    }
    </script>
</body>
</html>
