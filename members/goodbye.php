<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../styles.css" rel="stylesheet"/>
    

    <script>
        // Some JavaScript code that reloads the parent page ONLY ONCE
        window.onload = function () {
            if (!sessionStorage.getItem('parentReloaded')) {
                // Reload the parent page
                window.parent.location.reload();
                
                // Set a flag to ensure it doesn't reload again
                sessionStorage.setItem('parentReloaded', 'true');
            }
        };
    </script>
</head>
<body>

    <h1> Goodbye! </h1>
    <p> we are sad to see you go :(</p>

</body>
</html>