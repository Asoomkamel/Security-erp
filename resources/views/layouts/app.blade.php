<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'نظام إدارة شركة الحراسات')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background:#f4f6f8; }
        .card { border:none; box-shadow:0 1px 3px rgba(0,0,0,.08); }
        .kpi-value { font-size:1.6rem; font-weight:700; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <span class="navbar-brand">🛡️ نظام إدارة شركة الحراسات</span>
            <div>
                <a href="{{ route('reports.dashboard') }}" class="btn btn-sm btn-outline-light">لوحة التحكم</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
