<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'كلية التقنية الإلكترونية - طرابلس' }}</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            font-family: "Tajawal", "Cairo", sans-serif;
            background: #f3f4f6;
        }

        .hero-overlay {
            background:
                linear-gradient(to left, rgba(0,0,0,.72), rgba(0,0,0,.45)),
                linear-gradient(to top, rgba(255,255,255,.85), rgba(255,255,255,0) 20%);
        }
    </style>
</head>
<body class="min-h-screen text-slate-900">

<header class="bg-white shadow-sm">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex items-center justify-between h-28">

            <div class="flex items-center gap-4">
                <div class="text-right">
                    <h1 class="text-2xl font-bold text-slate-800">
                        كلية التقنية الإلكترونية - طرابلس
                    </h1>
                    <p class="text-sm text-slate-500">
                        College of Electronic Technology - Tripoli
                    </p>
                </div>

                <div class="w-14 h-14 rounded-full border-2 border-sky-400 flex items-center justify-center text-sky-500 text-2xl font-bold">
                    CET
                </div>
            </div>

            <nav class="hidden md:flex items-center gap-10 text-lg font-semibold text-slate-700">
                <a href="#" class="hover:text-blue-700 transition">الرئيسية</a>
                <a href="#" class="hover:text-blue-700 transition">عن الكلية</a>
                <a href="#" class="hover:text-blue-700 transition">هيئة التدريس</a>
                <a href="#" class="hover:text-blue-700 transition">الأقسام</a>
                <a href="#" class="hover:text-blue-700 transition">الأخبار</a>
                <a href="#" class="hover:text-blue-700 transition">اتصل بنا</a>
            </nav>

            <div>
                <a href="{{ route('registration.create') }}"
                   class="inline-flex items-center justify-center rounded-xl bg-blue-800 px-8 py-3 text-lg font-bold text-white shadow hover:bg-blue-900 transition">
                    التسجيل
                </a>
            </div>
        </div>
    </div>
</header>

<section class="relative overflow-hidden">
    <div class="absolute inset-0">
        <img
            src="https://images.unsplash.com/photo-1562774053-701939374585?q=80&w=1600&auto=format&fit=crop"
            alt="College"
            class="w-full h-full object-cover"
        >
        <div class="hero-overlay absolute inset-0"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-6 py-24 lg:py-32">
        <div class="max-w-3xl mr-auto text-right text-white">
                <span class="inline-flex rounded-full bg-white/15 px-5 py-2 text-sm font-medium backdrop-blur">
                    المؤسسة التعليمية الرائدة في مجال التقنية
                </span>

            <h2 class="mt-6 text-5xl lg:text-7xl font-extrabold leading-tight">
                كلية التقنية الإلكترونية
                <br>
                طرابلس
            </h2>

            <p class="mt-6 text-2xl text-slate-200">
                نحو مستقبل رقمي أفضل
            </p>

            <div class="mt-10">
                <a href="#registration-form"
                   class="inline-flex items-center justify-center rounded-full border border-white/70 px-8 py-4 text-lg font-bold text-white hover:bg-white hover:text-slate-900 transition">
                    التقديم على الدراسات العليا
                </a>
            </div>
        </div>
    </div>
</section>

<main id="registration-form" class="max-w-7xl mx-auto px-6 py-16">
    {{ $slot }}
</main>

</body>
</html>
