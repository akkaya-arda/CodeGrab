<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth Failed</title>
</head>

<body
    style="display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; font-family: sans-serif; color: #fff; background: #1a1a1a;">
    <div style="text-align: center; padding: 250px;">

        <h1 style="color: #e74c3c;">{{ strtoupper($error) }}</h1>
        <p>{{ $error_description }}</p>
    </div>
</body>

</html>