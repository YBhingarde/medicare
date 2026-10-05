<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Prescription {{ $a->rxNumber() }}</title>
    @include('prescriptions._style')
</head>

<body style="margin:0">
    @include('prescriptions._body')
</body>

</html>
