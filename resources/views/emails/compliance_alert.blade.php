<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $subjectLine }}</title>
</head>
<body style="font-family: Arial, sans-serif;">
    <h2>{{ $subjectLine }}</h2>
    <pre style="background:#f6f8fa;padding:12px;border-radius:6px;">{{ json_encode($data, JSON_PRETTY_PRINT) }}</pre>
    <p style="color:#666;font-size:12px;">This is an automated compliance alert from TexaPay.</p>
</body>
</html>
