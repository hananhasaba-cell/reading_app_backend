<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>حالة اقتراح الكتاب</title>
</head>
<body>
    <h2>مرحباً {{ $user->name }}</h2>
    <p>
        حالة اقتراحك للكتاب:
        <strong>{{ $suggestion->title }}</strong>
        <br>
        <strong>الحالة:</strong> {{ $status }}
    </p>
    @if($accepted)
        <p>تمت إضافة الكتاب إلى قائمة الكتب بنجاح.</p>
    @else
        <p>نعتذر، لم يتم قبول اقتراحك هذه المرة.</p>
    @endif
</body>
</html>
