<!DOCTYPE html>
<html>
<head>
    <title>Redirecting to App...</title>
</head>
<body>
    <script>
        // Try to open the app immediately
        window.location.href = "{{ $appUrl }}";

        // If the app doesn't open within 2 seconds, show a manual button
        setTimeout(function() {
            document.body.innerHTML = `
                <div style="text-align:center; padding-top: 50px; font-family: sans-serif;">
                    <h2>Opening Sleepywears...</h2>
                    <p>If the app didn't open automatically, click below:</p>
                    <a href="{{ $appUrl }}" style="padding: 10px 20px; background: #0D0F66; color: white; text-decoration: none; border-radius: 5px;">Open App</a>
                </div>
            `;
        }, 2000);
    </script>
</body>
</html>
